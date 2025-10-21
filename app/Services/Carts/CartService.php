<?php

namespace App\Services\Carts;

use App\Exceptions\OutOfStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Stocks\StockService;
use Exception;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

readonly class CartService
{
    public function __construct(private StockService $stockService) {}

    public function findActiveByUser(int $userId): ?Cart
    {
        /**
         * Find an active cart for a given user ID
         * Returns null if no active cart is found
         */
        return Cart::where('user_id', $userId)->where('status', 'active')->lockForUpdate()->first();
    }

    public function findActiveByTokenHash(string $hash): ?Cart
    {
        /**
         * Find an active cart for a given cart token
         * Returns null if no active cart is found
         */
        return Cart::where('cart_token_hash', $hash)->where('status', 'active')->lockForUpdate()->first();
    }

    /**
     * Creating a cart for the authenticated user or guest is separated into two methods for clarity, maintainability, single responsibility and better readability.
     */
    /**
     * @throws Throwable
     */
    public function createUserCart(int $userId): Cart
    {
        /**
         * Create a new cart for the authenticated user
         **/
        return DB::transaction(function () use ($userId) {
            // Check if the user already has an active cart
            $userHasCart = $this->findActiveByUser($userId);

            // If they do, return it
            if ($userHasCart) {
                return $userHasCart;
            }

            // Otherwise, create a new cart
            return Cart::create([
                'user_id' => $userId,
                'status' => 'active',
                'expires_at' => now()->addDays(15),
            ]);
        });
    }

    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function createGuestCart($hash): Cart
    {
        /**
         * Create a new cart for the guest user
         **/
        return DB::transaction(function () use ($hash) {
            // Check if the guest already has a cart
            $guestCart = $this->findActiveByTokenHash($hash);

            // If they do, return it
            if ($guestCart) {
                return $guestCart;
            }

            // Otherwise, create a new cart
            return Cart::create([
                'cart_token_hash' => $hash,
                'status' => 'active',
                'expires_at' => now()->addDays(7),
            ]);
        });
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function addProductToCart(int $cartId, array $productData): Cart
    {
        /**
         * Add a product to the cart with quantity 1
         * We're assuming $productData contains 'product_id', and 'product_variant_id' (if applicable)
         */
        return DB::transaction(function () use ($cartId, $productData) {
            // Find the cart by ID and ensure it's active
            $cart = Cart::whereKey($cartId)->where('status', 'active')->first();

            // If the cart doesn't exist or isn't active, throw an exception
            if (! $cart) {
                throw new Exception('Cart not found or inactive');
            }

            // Find the product by ID
            $product = Product::whereKey($productData['product_id'])->firstOrFail();

            // Check if the product_variant_id is provided in the request data
            $dataHasVariant = array_key_exists('product_variant_id', $productData) && ! is_null($productData['product_variant_id']);

            // If product_variant_id is provided, check if the variant exists and belongs to the given product
            $variant = $dataHasVariant ? ProductVariant::whereKey($productData['product_variant_id'])->where('product_id', $productData['product_id'])->firstOrFail() : null;

            // Check if the product (and variant, if applicable) is already in the cart
            $cartItemQty = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $productData['product_id'])
                ->when($dataHasVariant, function ($query) use ($productData) {
                    return $query->where('product_variant_id', $productData['product_variant_id']);
                }, function ($query) {
                    return $query->whereNull('product_variant_id');
                })
                ->value('quantity') ?? 0;

            // Check stock availability
            $hasSufficientStock = $dataHasVariant
                ? $this->stockService->hasSufficientStockForVariant($productData['product_variant_id'], $cartItemQty + 1)
                : $this->stockService->hasSufficientStockForProduct($productData['product_id'], $cartItemQty + 1);

            if (! $hasSufficientStock) {
                throw new OutOfStockException('Insufficient stock for the requested product or variant');
            }

            // Define product name
            $name = $dataHasVariant
                ? $product->name.' - '.$variant->sku
                : $product->name;

            // Determine the unit price based on whether a variant is specified
            $unitPrice = $dataHasVariant
                ? $variant->price
                : $product->price;

            // Calculate the line subtotal gross
            $lineSubtotalGross = round((float) $unitPrice * ($cartItemQty + 1), 2);

            // Upsert the product into the cart with the specified quantity
            $upsertQuery = '
            INSERT INTO cart_items (cart_id, product_id, product_variant_id, unit_gross_price, line_subtotal_gross, created_at, updated_at, name)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)
            ON CONFLICT (cart_id, product_id, variant_key)
            DO UPDATE SET quantity = cart_items.quantity + 1,
                        line_subtotal_gross = cart_items.unit_gross_price * (cart_items.quantity + 1),
                        updated_at = NOW()
            ';

            DB::statement($upsertQuery, [
                $cart->id,
                $productData['product_id'],
                $dataHasVariant ? $productData['product_variant_id'] : null,
                $unitPrice,
                $lineSubtotalGross,
                $name,
            ]);

            // Update the cart's subtotal_gross
            DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id= ?), 0), updated_at = NOW() WHERE id = ?',
                [$cart->id, $cart->id]);

            return $cart->refresh();
        });
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function increaseCartItemQuantity(int $cartId, int $cartItemId): CartItem
    {
        /**
         * Increase the quantity of a cart item by 1
         */
        return DB::transaction(function () use ($cartId, $cartItemId) {
            // Lock the cart item row for update
            $cartItem = CartItem::whereKey($cartItemId)
                ->where('cart_id', $cartId)
                ->lockForUpdate()
                ->firstOrFail();

            // Check stock availability depending on whether it's a product or variant
            $itemType = $cartItem->product_variant_id ? 'variant' : 'product';
            $hasSufficientStock = $itemType === 'variant'
                ? $this->stockService->hasSufficientStockForVariant($cartItem->product_variant_id, $cartItem->quantity + 1)
                : $this->stockService->hasSufficientStockForProduct($cartItem->product_id, $cartItem->quantity + 1);

            // If there's not enough stock, throw an exception
            if (! $hasSufficientStock) {
                throw new OutOfStockException('Insufficient stock for the requested product or variant');
            }

            $cartItem->quantity += 1;
            $cartItem->line_subtotal_gross = round($cartItem->unit_gross_price * ($cartItem->quantity), 2);
            $cartItem->updated_at = now();
            $cartItem->save();

            // Update the cart's subtotal_gross
            DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id = ?), 0), updated_at = NOW() WHERE id = ?', [$cartId, $cartId]);

            return $cartItem->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function decreaseCartItemQuantity(int $cartId, int $cartItemId): CartItem
    {
        /**
         * Decrease the quantity of a cart item by 1
         */
        return DB::transaction(function () use ($cartId, $cartItemId) {
            // Lock the cart item row for update
            $cartItem = CartItem::whereKey($cartItemId)
                ->where('cart_id', $cartId)
                ->lockForUpdate()
                ->firstOrFail();

            // If the quantity is 1, remove the item from the cart
            if ($cartItem->quantity === 1) {
                $cartItem->delete();

                // Update the cart's subtotal_gross
                DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id = ?), 0), updated_at = NOW() WHERE id = ?', [$cartId, $cartId]);

                return $cartItem;
            }

            // Otherwise, decrease the quantity by 1
            $cartItem->quantity -= 1;
            $cartItem->line_subtotal_gross = round($cartItem->unit_gross_price * ($cartItem->quantity), 2);
            $cartItem->updated_at = now();
            $cartItem->save();

            // Update the cart's subtotal_gross
            DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id = ?), 0), updated_at = NOW() WHERE id = ?', [$cartId, $cartId]);

            return $cartItem->refresh();
        });
    }

    /**
     * @throws Throwable
     */
    public function deleteCartItem(int $cartId, int $cartItemId): void
    {
        /**
         * Delete a cart item from the cart
         */
        DB::transaction(function () use ($cartId, $cartItemId) {
            // Find the cart item
            $cartItem = CartItem::whereKey($cartItemId)
                ->where('cart_id', $cartId)
                ->lockForUpdate()
                ->firstOrFail();

            // Delete the cart item
            $cartItem->delete();

            // Update the cart's subtotal_gross
            DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id = ?), 0), updated_at = NOW() WHERE id = ?', [$cartId, $cartId]);
        });
    }

    /**
     * @throws Throwable
     */
    public function clearCart(int $cartId): void
    {
        /**
         * Clear all items from the cart
         */
        DB::transaction(function () use ($cartId) {
            // Find the cart to ensure it exists and is active
            Cart::whereKey($cartId)->where('status', 'active')->firstOrFail();

            // Delete all cart items for the given cart ID
            CartItem::where('cart_id', $cartId)->delete();

            // Update the cart's subtotal_gross to 0
            DB::update('UPDATE carts SET subtotal_gross = 0, updated_at = NOW() WHERE id = ?', [$cartId]);
        });
    }

    public function getUserCart(int $cartId, int $userId): Cart
    {
        return Cart::whereKey($cartId)
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where('status', 'active')
            ->with(['items:id,cart_id,product_id,product_variant_id,quantity,unit_gross_price,line_subtotal_gross,created_at'])
            ->firstOrFail();
    }

    public function getGuestCart(string $hash): Cart
    {
        return Cart::where('cart_token_hash', $hash)
            ->where('expires_at', '>', now())
            ->where('status', 'active')
            ->with(['items:id,cart_id,product_id,product_variant_id,variant_key,quantity,unit_gross_price,line_subtotal_gross,created_at'])
            ->firstOrFail();
    }

    /**
     * @throws RandomException
     */
    public function generateCartToken(int $bytes = 16): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public function hashCartToken(string $cartToken): string
    {
        return hash('sha256', $cartToken);
    }
}

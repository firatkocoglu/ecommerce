<?php

namespace App\Services\Carts;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class CartService
{
    public function findActiveByUser(int $userId): ?Cart
    {
        /**
         * Find an active cart for a given user ID
         * Returns null if no active cart is found
         */
        return Cart::where('user_id', $userId)->where('status', 'active')->first();
    }

    public function findActiveByToken(string $token): ?Cart
    {
        /**
         * Find an active cart for a given cart token
         * Returns null if no active cart is found
         */
        return Cart::where('cart_token', $token)->where('status', 'active')->first();
    }

    /**
     * Creating a cart for the authenticated user or guest is separated into two methods for clarity, maintainability, single responsibility and better readability.
     */
    /**
     * @throws Throwable
     */
    public function createUserCart(): Cart
    {
        /**
         * Create a new cart for the authenticated user
         **/
        if (auth()->check()) {
            // Get the authenticated user
            $user = auth()->user();

            return DB::transaction(function () use ($user) {
                // Check if the user already has an active cart
                $userHasCart = $this->findActiveByUser($user->id);

                // If they do, return it
                if ($userHasCart) {
                    return $userHasCart;
                }

                // Otherwise, create a new cart
                return Cart::create([
                    'user_id' => $user->id,
                    'status' => 'active',
                    'expires_at' => now()->addDays(15),
                ]);
            });
        }
    }

    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function createGuestCart(): Cart
    {
        /**
         * Create a new cart for the guest user
         **/
        if (! auth()->check()) {
            return DB::transaction(function () {
                // Check if the guest already has a cart
                $guestCart = $this->findActiveByToken(request()->cookie('cart_token'));

                // If they do, return it
                if ($guestCart) {
                    return $guestCart;
                }

                // Otherwise, create a new cart
                return Cart::create([
                    'cart_token' => bin2hex(random_bytes(16)),
                    'status' => 'active',
                    'expires_at' => now()->addDays(7),
                ]);
            });
        }
    }

    /**
     * @throws Exception
     */
    public function addProductToCart(int $cartId, array $productData): Cart
    {
        /**
         * Add a product to the cart with the specified quantity
         * We're assuming $productData contains 'product_id', 'quantity' and 'product_variant_id' (if applicable)
         */
        // Validate the quantity
        $quantity = (int) $productData['quantity'];
        if ($quantity <= 0) {
            throw new Exception('Quantity must be a positive integer');
        }

        // Find the cart by ID and ensure it's active
        $cart = Cart::whereKey($cartId)->where('status', 'active')->first();

        // If the cart doesn't exist or isn't active, throw an exception
        if (! $cart) {
            throw new Exception('Cart not found or inactive');
        }

        // Find the product by ID
        $product = Product::whereKey($productData['product_id'])->exists();

        if (! $product) {
            throw new Exception('Product not found');
        }

        // Check if the product_variant_id is provided in the request data
        $dataHasVariant = array_key_exists('product_variant_id', $productData) && ! is_null($productData['product_variant_id']);

        // If product_variant_id is provided, check if the variant exists and belongs to the given product
        $variant = $dataHasVariant ? ProductVariant::whereKey($productData['product_variant_id'])->where('product_id', $productData['product_id'])->exists() : null;

        // If the product_variant_id is provided but the variant does not exist with given ID or the variant does not belong to given product, throw an exception
        if ($dataHasVariant && ! $variant) {
            throw new Exception('Product variant not found');
        }

        // Determine the unit price based on whether a variant is specified
        $unitPrice = $dataHasVariant ? ProductVariant::whereKey($productData['product_variant_id'])->value('price') : Product::whereKey($productData['product_id'])->value('price');

        // If the unit price is not found, throw an exception
        if (! $unitPrice) {
            throw new Exception('Product price not found');
        }

        // Calculate the line subtotal gross
        $lineSubtotalGross = round((float) $unitPrice * $quantity, 2);

        // Upsert the product into the cart with the specified quantity
        $upsertQuery = '
          INSERT INTO cart_items (cart_id, product_id, product_variant_id, quantity, unit_gross_price, line_subtotal_gross, created_at, updated_at)
          VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
          ON CONFLICT (cart_id, product_id, variant_key)
          DO UPDATE SET quantity = cart_items.quantity + EXCLUDED.quantity,
                        line_subtotal_gross = cart_items.unit_gross_price * (cart_items.quantity + EXCLUDED.quantity),
                        updated_at = NOW()
        ';

        DB::statement($upsertQuery, [
            $cart->id,
            $productData['product_id'],
            $dataHasVariant ? $productData['product_variant_id'] : null,
            $productData['quantity'],
            $unitPrice,
            $lineSubtotalGross,
        ]);

        // Update the cart's subtotal_gross
        DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items), 0), updated_at = NOW() WHERE id = ?',
        [$cart->id, $cart->id]);

        return $cart->refresh();
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

            $cartItem->quantity = $cartItem->quantity + 1;
            $cartItem->line_subtotal_gross = round($cartItem->unit_gross_price * ($cartItem->quantity + 1), 2);
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
            $cartItem->quantity = $cartItem->quantity - 1;
            $cartItem->line_subtotal_gross = round($cartItem->unit_gross_price * ($cartItem->quantity + 1), 2);
            $cartItem->updated_at = now();
            $cartItem->save();

            // Update the cart's subtotal_gross
            DB::update('UPDATE carts SET subtotal_gross = COALESCE((SELECT SUM(line_subtotal_gross) FROM cart_items WHERE cart_id = ?), 0), updated_at = NOW() WHERE id = ?', [$cartId, $cartId]);

            return $cartItem->refresh();
        });
    }
}

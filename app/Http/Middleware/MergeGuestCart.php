<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Services\Carts\CartService;
use App\Services\Stocks\StockService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class MergeGuestCart
{
    public function __construct(private CartService $cartService,
        private StockService $stockService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Logic to merge guest cart with authenticated user's cart
        if (auth()->check() && $request->cookie('cart_token') && ! session('cart_merged')) {
            DB::transaction(function () use ($request) {
                $hashedGuestCartToken = hash('sha256', $request->cookie('cart_token'));
                $user = auth()->user();

                // Retrieve guest cart using the hashed token
                $guestCart = $this->cartService->findActiveByTokenHash($hashedGuestCartToken);

                $guestCart?->load(['items' => function ($query) {
                    $query->select('id', 'cart_id', 'product_id', 'product_variant_id', 'variant_key', 'quantity', 'unit_gross_price', 'line_subtotal_gross');
                }]);

                // Check whether user has an active cart, if not create one
                $userCart = $this->cartService->findActiveByUser($user->id);

                if (! $userCart) {
                    $userCart = $this->cartService->createUserCart($user->id);
                }

                $userCart?->load(['items' => function ($query) {
                    $query->select('id', 'cart_id', 'product_id', 'product_variant_id', 'quantity', 'unit_gross_price', 'line_subtotal_gross');
                }]);

                // Merge items from guest cart to user's cart
                if ($guestCart) {
                    foreach ($guestCart->items as $item) {
                        $existingItem = $userCart->items
                            ->where('product_id', $item->product_id)
                            ->where('variant_key', $item->variant_key)
                            ->first();

                        if ($existingItem) {
                            // Check new stock availability
                            $targetQuantity = $existingItem->quantity + $item->quantity;

                            $hasSufficientStock = $item->product_variant_id
                                ? $this->stockService->hasSufficientStockForVariant($item->product_variant_id, $targetQuantity)
                                : $this->stockService->hasSufficientStockForProduct($item->product_id, $targetQuantity);

                            if (! $hasSufficientStock) {
                                $item->delete();

                                continue;
                            }

                            // If item exists, update quantity
                            $existingItem->quantity = $targetQuantity;
                            $existingItem->line_subtotal_gross = $existingItem->quantity * $existingItem->unit_gross_price;
                            $existingItem->save();
                        } else {
                            // If item does not exist, attach it to user's cart
                            $userCart->items()->create([
                                'product_id' => $item->product_id,
                                'product_variant_id' => $item->product_variant_id,
                                'quantity' => $item->quantity,
                                'unit_gross_price' => $item->unit_gross_price,
                                'line_subtotal_gross' => $item->line_subtotal_gross,
                            ]);
                        }
                        // Remove item from guest cart after merging
                        $item->delete();
                    }

                    // Calculate the new subtotal for the user's cart
                    DB::update('
                    UPDATE carts SET
                                     subtotal_gross = (SELECT COALESCE(SUM(line_subtotal_gross), 0) FROM cart_items WHERE cart_id = ?), updated_at = NOW() WHERE id = ?;
                    ', [$userCart->id, $userCart->id]);

                    DB::update("
                    UPDATE carts SET
                                     subtotal_gross = 0,
                                     status = 'merged',
                                     merged_into_cart_id = ?,
                                     cart_token_hash = NULL,
                                     expires_at = NOW()
                                     WHERE id = ?;
                        ", [$userCart->id, $guestCart->id]);
                }
                DB::afterCommit(function () {
                    cookie()->queue(cookie()->forget('cart_token')); // Clear guest cart token cookie
                    session()->put('cart_merged', true); // Set session flag to prevent re-merging
                });
            });
        }

        return $next($request);
    }
}

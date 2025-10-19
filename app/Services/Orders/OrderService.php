<?php

namespace App\Services\Orders;

use App\Models\Cart;
use App\Models\Order;
use App\Services\Carts\CartService;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

readonly class OrderService
{
    public function __construct(private CartService $cartService) {}

    /**
     * @throws Throwable
     */
    public function createOrder(array $orderData)
    {
        // Logic to create an order
        $userId = $orderData['user_id'];
        $cartId = $orderData['cart_id'];

        if (! $userId || ! $cartId) {
            abort(422, "User ID and Cart ID are required to create an order.");
        }

        // Check if the cart belongs to user and is active, if so retrieve it
        $cart = $this->cartService->getUserCart($userId, $cartId);

        // Ensure the cart is not empty
        if (! $cart->items->count()) {
            abort(422, "Cannot create order from an empty cart.");
        }

        return DB::transaction(function () use ($userId, $cart) {
            // Lock the cart for further update
            Cart::whereKey($cart->id)->lockForUpdate()->first();

            // Is cart merged from another cart?
            $sourceCartId = $cart->merged_into_cart_id ?? $cart->id;

            // Check if a pending order already exists for this cart and the user
            $existingOrder = Order::where('source_cart_id', $sourceCartId)
                ->where('status', 'pending')
                ->where('user_id', $userId)
                ->first();

            // If so, return the existing order to prevent duplicate orders
            if ($existingOrder) {
                return $existingOrder;
            }

            // Create the order from the cart
            $order = Order::create([
                'user_id' => $userId,
                'source_cart_id' => $sourceCartId,
                'grand_total' => $cart->subtotal_gross,
                'status' => 'pending',
                'currency_code' => 'TRY',
            ]);

            // Attach cart items to the order
            $this->attachItemsToOrder($order, $cart->items->toArray());

            // Calculate order totals and VAT
            $this->calculateOrderTotal($order);
            $this->calculateVATForOrder($order);

            return $order;
        });
    }

    public function getOrderById(int $orderId)
    {
        // Logic to retrieve an order by its ID
    }

    public function listOrdersByUser(int $userId)
    {
        // Logic to list orders belonging to a user}
    }

    public function markPaid(int $orderId)
    {
        // Logic to mark an order as paid

    }

    public function cancelOrder(int $orderId)
    {
        // Logic to cancel an order
    }

    public function removeItemFromOrder(int $orderId)
    {
        // Logic to refund an order
    }

    /**
     * @throws JsonException
     */
    private function attachItemsToOrder(Order $order, array $cartItems): void
    {
        $jsonItems = json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $query = "INSERT INTO order_items (order_id, product_id, product_variant_id, quantity, unit_gross_price, name, created_at, updated_at)
                  SELECT
                    :order_id,
                    ci.product_id,
                    ci.product_variant_id,
                    ci.quantity,
                    ci.unit_gross_price,
                    ci.name,
                    NOW(),
                    NOW()
                  FROM json_to_recordset(:json_items) AS ci(
                    product_id INT,
                    product_variant_id INT,
                    quantity INT,
                    unit_gross_price DECIMAL(10,2),
                    name VARCHAR
                        )
                  ON CONFLICT (order_id, product_id, variant_key) DO NOTHING
                  ";

                  DB::statement($query, [
                    'order_id' => $order->id,
                    'json_items' => $jsonItems
                  ]);
    }

    private function calculateOrderTotal(Order $order): void
    {
        DB::statement("
            UPDATE orders SET grand_total = (
                SELECT SUM(subtotal_line_gross) FROM order_items WHERE order_id = :order_id
            ) WHERE order_id = :order_id
        ", [
            'order_id' => $order->id,
            'order_id' => $order->id
        ]);
    }

    private function calculateVATForOrder(Order $order, float $vat = 1.2): void
    {
        // Logic to calculate VAT for the order
        DB::statement("
            UPDATE orders SET net_total = ROUND(grand_total / :vat, 2),
                              vat_amount = grand_total - ROUND(grand_total / :vat, 2)
            WHERE id = :order_id
        ", [
            'order_id' => $order->id,
            'vat' => $vat
        ]);
    }
}

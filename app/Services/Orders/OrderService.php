<?php

namespace App\Services\Orders;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Exceptions\OutOfStockException;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Carts\CartService;
use App\Services\Stocks\StockService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

readonly class OrderService
{
    public function __construct(private StockService $stockService, private CartService $cartService) {}

    /**
     * @throws Throwable
     */
    public function createOrder(array $orderData)
    {
        // Logic to create an order
        $userId = $orderData['user_id'];
        $cartId = $orderData['cart_id'];

        if (! $userId || ! $cartId) {
            abort(422, 'User ID and Cart ID are required to create an order.');
        }

        return DB::transaction(function () use ($userId, $cartId) {
            // Lock the cart for further update
            $cart = Cart::whereKey($cartId)
                ->where('user_id', $userId)
                ->where('status', CartStatus::ACTIVE->value)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure the cart is not empty
            if (! $cart->items->count()) {
                abort(422, 'Cannot create order from an empty cart.');
            }

            // Is cart merged from another cart?
            $sourceCartId = $cart->merged_into_cart_id ?? $cart->id;

            // Check if a pending order already exists for this cart and the user
            $existingOrder = Order::where('source_cart_id', $sourceCartId)
                ->where('status', OrderStatus::PENDING->value)
                ->where('user_id', $userId)
                ->first();

            // If so, return the existing order to prevent duplicate orders
            if ($existingOrder) {
                return $existingOrder;
            }

            // Assert stock availability for the cart items
            $this->assertStockForOrderItems($sourceCartId);

            // Create the order from the cart
            $order = Order::create([
                'user_id' => $userId,
                'source_cart_id' => $sourceCartId,
                'grand_total' => $cart->subtotal_gross,
                'status' => OrderStatus::PENDING->value,
                'currency_code' => 'TRY',
            ]);

            // Attach cart items to the order
            $this->attachItemsToOrder($order, $cart->items->toArray());

            // Calculate order totals and VAT
            $this->calculateOrderTotal($order);
            $this->calculateVATForOrder($order);

            // Close the cart
            $this->closeCart($cart);

            return $order->refresh();
        });
    }

    public function getOrderById(int $orderId, int $userId): Order
    {
        // Logic to retrieve an order by its ID
        return Order::whereKey($orderId)
            ->where('user_id', $userId)
            ->with(['items', 'payment'])
            ->firstOrFail();
    }

    public function listOrdersByUser(int $userId): LengthAwarePaginator
    {
        // Logic to list orders belonging to a user
        return Order::where('user_id', $userId)
            ->with('items:id,order_id,quantity,name,created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * @throws Throwable
     */
    public function markPaid(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            // Lock the order for update
            $order = Order::lockForUpdate()->findOrFail($orderId);

            // If already paid, do nothing
            if ($order->status !== OrderStatus::PENDING) {
                return;
            }

            // Deduct stock for each item in the order
            $query = "
                WITH ordered AS (
                    SELECT oi.product_id, oi.product_variant_id, oi.quantity AS requested
                    FROM order_items oi
                    WHERE oi.order_id = :order_id
                    FOR UPDATE
                ),
                variant_check AS (
                    SELECT
                        v.id            AS variant_id,
                        o.requested
                    FROM ordered o
                    JOIN product_variants v ON v.id = o.product_variant_id
                    WHERE o.product_variant_id IS NOT NULL
                    ORDER BY v.id
                    FOR UPDATE OF v
                ),
                product_check AS (
                    SELECT
                        p.id            AS product_id,
                        o.requested
                    FROM ordered o
                    JOIN products p     ON p.id = o.product_id
                    WHERE o.product_variant_id IS NULL
                    ORDER BY p.id
                    FOR UPDATE OF p
                ),
                all_deductions AS (
                    SELECT 'variant' AS sku_type, variant_id AS sku_id,  requested FROM variant_check
                UNION ALL
                    SELECT 'product' AS sku_type, product_id AS sku_id, requested FROM product_check
                )
                UPDATE stocks SET quantity = quantity - ad.requested
                FROM all_deductions ad
                WHERE
                    (ad.sku_type='variant' AND stocks.product_variant_id = ad.sku_id AND stocks.quantity >= ad.requested)
                    OR
                    (ad.sku_type='product' AND stocks.product_id = ad.sku_id AND stocks.quantity >= ad.requested);
            ";

            $affected = DB::affectingStatement($query, [
                'order_id' => $orderId,
            ]);

            $expected = DB::table('order_items')
                ->where('order_id', $orderId)
                ->count();

            if ($affected !== $expected) {
                throw new OutOfStockException('Insufficient stock to fulfill the order.', [], 409);
            }

            // Mark the order as paid
            $order->update([
                'status' => OrderStatus::PAID->value,
            ]);
        });
    }

    /**
     * @throws Throwable
     */
    public function cancelOrder(int $orderId): void
    {
        DB::transaction(function () use ($orderId) {
            // Logic to cancel an order
            $order = Order::lockForUpdate()->findOrFail($orderId);

            // If already cancelled, do nothing
            if ($order->status === OrderStatus::CANCELLED->value) return;

            // Only pending, processing, or failed orders can be cancelled by user
            if ($order->status === OrderStatus::PENDING->value || $order->status === OrderStatus::PROCESSING->value || $order->status === OrderStatus::FAILED->value) {
                $order->update([
                    'status' => OrderStatus::CANCELLED->value,
                    'cancelled_at' => NOW(),
                ]);
            } else {
                abort(422, 'Only pending, processing, or failed orders can be cancelled.');
            }
        });
    }

    public function removeItemFromOrder(int $orderId)
    {
        // Logic to refund an order
    }

    private function assertStockForOrderItems(int $cartId): void
    {
        $query = "WITH cart_rows AS (
                    SELECT ci.product_id, ci.product_variant_id, ci.quantity AS requested
                    FROM cart_items ci
                    WHERE ci.cart_id = :cart_id
                    ),
                variant_check AS (
                    SELECT
                    'variant'::text AS sku_type,
                    v.id            AS sku_id,
                    p.name          AS product_name,
                    v.sku           AS variant_sku,
                    cr.requested,
                    COALESCE(vs.quantity, 0) AS available
                FROM cart_rows cr
                JOIN product_variants v ON v.id = cr.product_variant_id
                JOIN products p        ON p.id = v.product_id
                LEFT JOIN stocks vs ON vs.product_variant_id = v.id
                WHERE cr.product_variant_id IS NOT NULL
                ORDER BY v.id
                FOR UPDATE OF v, vs
                ),
                product_check AS (
                SELECT
                    'product'::text AS sku_type,
                    p.id            AS sku_id,
                    p.name          AS product_name,
                    NULL::text      AS variant_sku,
                    cr.requested,
                    COALESCE(ps.quantity, 0) AS available
                FROM cart_rows cr
                JOIN products p     ON p.id = cr.product_id
                LEFT JOIN stocks ps ON ps.product_id = p.id
                WHERE cr.product_variant_id IS NULL
                ORDER BY p.id
                FOR UPDATE OF p, ps
                ),
                all_check AS (
                   SELECT *, (available >= requested) AS ok_flag FROM variant_check
                UNION ALL
                SELECT *, (available >= requested) AS ok_flag FROM product_check
                )
                SELECT *
                FROM all_check
                WHERE ok_flag = false;
                ";

        $insufficientRows = DB::select($query, [
            'cart_id' => $cartId,
        ]);

        if (count($insufficientRows) > 0) {
            throw new OutOfStockException(
                'Some of the items are out of stock.',
                $insufficientRows, // violations
                409
            );
        }
    }

    /**
     * @throws JsonException
     */
    private function attachItemsToOrder(Order $order, array $cartItems): void
    {
        $jsonItems = json_encode($cartItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $query = 'INSERT INTO order_items (order_id, product_id, product_variant_id, quantity, unit_gross_price, name, created_at, updated_at)
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
                    name TEXT
                        )
                  ON CONFLICT (order_id, product_id, variant_key) DO NOTHING
                  ';

        DB::statement($query, [
            'order_id' => $order->id,
            'json_items' => $jsonItems,
        ]);
    }

    private function calculateOrderTotal(Order $order): void
    {
        DB::statement('
            UPDATE orders SET grand_total = (
                SELECT SUM(subtotal_line_gross) FROM order_items
                WHERE order_id = :order_id
            ) WHERE id = :order_id
        ', [
            'order_id' => $order->id,
        ]);
    }

    private function calculateVATForOrder(Order $order, float $vat = 1.2): void
    {
        // Logic to calculate VAT for the order
        DB::statement('
            UPDATE orders SET net_total = ROUND(grand_total / :vat, 2),
                              vat_amount = grand_total - ROUND(grand_total / :vat, 2)
            WHERE id = :order_id
        ', [
            'order_id' => $order->id,
            'vat' => $vat,
        ]);
    }

    private function closeCart(Cart $cart): void
    {
        // Logic to close the cart after order creation
        if ($cart->status !== CartStatus::ACTIVE->value) {
            return;
        }

        $cart->update([
            'status' => CartStatus::ORDERED->value,
            'expires_at' => NOW(),
        ]);
    }
}

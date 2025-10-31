<?php

namespace App\Services\ReturnRequests;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Refunds\RefundService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

readonly class ReturnRequestService
{

    public function __construct(private RefundService $refundService)
    {
    }
    /**
     * @throws Throwable
     */
    public function createReturnRequest(array $data)
    {
        // Logic to create a return request
        // Data array may include order_id, reason, items.
        $userId = $data['user_id'];
        $orderId = $data['order_id'];
        $orderItems = $data['order_items'] ?? []; // expected : [['id' => int, 'quantity' => int], ...]
        $reason = $data['reason'] ?? 'No reason provided';

        return DB::transaction(function () use ($userId, $orderId, $orderItems, $reason) {
            if (empty($orderItems)) {
                throw new \Exception('You need to specify at least one item to return.');
            }

            // Check if the order belongs to the user and is eligible for return
            $order = Order::whereKey($orderId)
                ->where('user_id', $userId)
                ->whereIn('status', ['paid', 'shipped', 'completed'])
                ->firstOrFail();

            if ($order->fulfilled_at?->diffInDays(now()) > 14) {
                throw new \Exception('The order is no longer eligible for return (exceeded 14 days from fulfillment).');
            }

            // Check if a return request already exists for this order
            $existingRequest = ReturnRequest::where('order_id', $orderId)
                ->where('user_id', $userId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                throw new \Exception('A pending return request for this order already exists.');
            }

            $created = ReturnRequest::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'reason' => $reason,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Validate and normalize order items
            $normalizedItems = $this->validateAndNormalizeOrderItems($order, $orderItems);

            // Attach order items to the return request
            $this->attachOrderItemsToReturnRequest($created, $normalizedItems);

            return $created->refresh();
        });
    }

    private function validateAndNormalizeOrderItems(Order $order, array $orderItems): array
    {
        // Get all order items by their IDs for quick lookup
        $allOrderItemsById = $order->items->keyBy('id');

        $normalizedItems = [];

        // Validate each item
        foreach ($orderItems as $item) {
            // If id or quantity is missing or invalid
            if (!isset($item['id']) || !isset($item['quantity'])
                || !is_numeric($item['quantity']) || $item['quantity'] <= 0
                || !is_numeric($item['id']) || $item['id'] <= 0) {
                throw new \InvalidArgumentException('Return requests must have items.');
            }

            // Check if the item exists in the order
            if (!$allOrderItemsById->has($item['id'])) {
                throw new \InvalidArgumentException("Order item does not exist in the order.");
            }

            // Check if the item already exists in normalized items to aggregate quantity
            if (in_array($item['id'], array_column($normalizedItems, 'id'))) {
                $index = array_search($item['id'], array_column($normalizedItems, 'id'));
                $normalizedItems[$index]['quantity'] += (int)$item['quantity'];
                continue;
            }

            // Add item id and quantity to normalized items
            $normalizedItems[] = [
                'id' => (int)$item['id'],
                'quantity' => (int)$item['quantity'],
            ];
        }
        return $normalizedItems;
    }

    /**
     * @throws JsonException
     */
    private function attachOrderItemsToReturnRequest(ReturnRequest $returnRequest, array $orderItems): void
    {
        $jsonOrderItems = json_encode($orderItems, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $query = "
            INSERT INTO return_request_items (return_request_id, order_item_id, quantity, condition, evidence_urls, created_at, updated_at)
            SELECT
                :return_request_id,
                oi.id,
                oi.quantity,
                'new',
                '[]',
                NOW(),
                NOW()
            FROM json_to_recordset(:json_order_items) AS oi(id INT, quantity INT)
            ON CONFLICT (return_request_id, order_item_id)
                DO UPDATE SET
                quantity = return_request_items.quantity + EXCLUDED.quantity,
                updated_at = NOW();
            ";

        DB::statement($query, [
            'return_request_id' => $returnRequest->id,
            'json_order_items' => $jsonOrderItems,
        ]);
    }

    public function listReturnRequestsByUser(int $userId): Collection
    {
        return ReturnRequest::where('user_id', $userId)
            ->with('items')
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    public function getReturnRequestByUser(int $userId, int $returnRequestId): ReturnRequest
    {
        return ReturnRequest::whereKey($returnRequestId)
            ->where('user_id', $userId)
            ->with('items')
            ->firstOrFail();
    }

    public function deleteReturnRequest(int $userId, int $returnRequestId): void
    {
        // User can only delete their own pending return requests
        $returnRequest = ReturnRequest::where('user_id', $userId)
            ->where('id', $returnRequestId)
            ->where('status', 'pending')
            ->firstOrFail();

        $returnRequest->delete();
    }


    public function adminListReturnRequests(): Collection
    {
        return ReturnRequest::with('items')
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    public function adminGetReturnRequest(int $returnRequestId): ReturnRequest
    {
        return ReturnRequest::where('id', $returnRequestId)
            ->with('items')
            ->firstOrFail();
    }

    public function adminDeleteReturnRequest(int $returnRequestId): void
    {
        $returnRequest = ReturnRequest::where('id', $returnRequestId)->firstOrFail();
        $returnRequest->delete();
    }

    /**
     * @throws Throwable
     */
    public function adminApproveReturnRequest(int $returnRequestId, int $adminId): void
    {
        DB::transaction(function () use ($returnRequestId, $adminId) {
            $returnRequest = ReturnRequest::where('id', $returnRequestId)
                ->where('status', 'pending')
                ->firstOrFail();

            // Prepare refund data and calculate refund amount
            $refundData = [
                'return_request_id' => $returnRequest->id,
                'order_id' => $returnRequest->order_id,
                'user_id' => $returnRequest->user_id,
                'amount' => $this->calculateRefundAmount($returnRequest)['amount'],
                'currency_code' => $this->calculateRefundAmount($returnRequest)['currency_code'],
                'reason' => 'Return approved',
            ];

            $returnRequest->status = 'approved';
            $returnRequest->admin_id = $adminId;
            $returnRequest->approved_at = now();
            $returnRequest->save();

            // Trigger refund process
            $this->refundService->createRefund($refundData);
        });
    }

    public function adminRejectReturnRequest(int $returnRequestId, int $adminId, string $reason): void
    {
        $returnRequest = ReturnRequest::where('id', $returnRequestId)
            ->where('status', 'pending')
            ->firstOrFail();

        $returnRequest->status = 'rejected';
        $returnRequest->admin_id = $adminId;
        $returnRequest->notes = $reason;
        $returnRequest->rejected_at = now();
        $returnRequest->save();
    }

    private function calculateRefundAmount(ReturnRequest $returnRequest): array
    {
        $query =
            "SELECT COALESCE(SUM(ROUND(oi.unit_gross_price * rri.quantity, 2)), 0) AS refund_amount,
            o.currency_code
            FROM return_request_items rri
            JOIN order_items oi On rri.order_item_id = oi.id
            JOIN orders o ON o.id = oi.order_id
            WHERE rri.return_request_id = :return_request_id
            AND oi.order_id = (SELECT order_id FROM return_requests WHERE id = :return_request_id)
            GROUP BY o.currency_code;
            ";

         $result = DB::selectOne($query, [
            'return_request_id' => $returnRequest->id,
        ]);

         return ['amount' => (float)$result->refund_amount, 'currency_code' => $result->currency_code];
    }
}

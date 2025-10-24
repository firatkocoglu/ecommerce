<?php

namespace App\Services\ReturnRequests;

use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use JsonException;
use LaravelIdea\Helper\App\Models\_IH_ReturnRequest_C;
use Throwable;

class ReturnRequestService
{
    /**
     * @throws Throwable
     */
    public function createReturnRequest(array $data)
    {
        // Logic to create a return request
        // Data array may include order_id, reason, items.

        $userId = $data['user_id'];
        $orderId = $data['order_id'];
        $reason = $data['reason'] ?? 'No reason provided';

        return DB::transaction(function () use ($userId, $orderId, $reason) {
            // Check if the order belongs to the user and is eligible for return
            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->where('fulfilled_at', '>=', now()->subDays(14))
                ->firstOrFail();

            // Obtain order items
            $orderItems = $order->items;

            // Check if a return request already exists for this order
            $existingRequest = ReturnRequest::where('order_id', $orderId)
                ->where('user_id', $userId)
                ->first();

            if ($existingRequest) {
                throw new \Exception('A return request for this order already exists.');
            }

            $created = ReturnRequest::create([
                'user_id' => $userId,
                'order_id' => $orderId,
                'reason' => $reason,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Link order items to the return request
            if (empty($orderItemIds)) {
                throw new \Exception('No order items specified for return.');
            }
            $this->attachOrderItemsToReturnRequest($created, $orderItems);

            return $created->refresh();
        });
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
            ON CONFLICT (return_request_id, order_item_id) DO UPDATE SET
                quantity = return_request_items.quantity + EXCLUDED.quantity;
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

    public function getReturnRequestByUser(int $userId, int $returnRequestId): ?ReturnRequest
    {
        return ReturnRequest::where('user_id', $userId)
            ->where('id', $returnRequestId)
            ->with('items')
            ->firstOrFail();
    }


    public function adminListReturnRequests(): _IH_ReturnRequest_C
    {
        return ReturnRequest::with('items')
            ->orderBy('requested_at', 'desc')
            ->get();
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

    public function adminDeleteReturnRequest(int $returnRequestId): void
    {
        $returnRequest = ReturnRequest::where('id', $returnRequestId)->firstOrFail();
        $returnRequest->delete();
    }

    public function adminApproveReturnRequest(int $returnRequestId, int $adminId): void
    {
        $returnRequest = ReturnRequest::where('id', $returnRequestId)
            ->where('status', 'pending')
            ->firstOrFail();

        $returnRequest->status = 'approved';
        $returnRequest->admin_id = $adminId;
        $returnRequest->approved_at = now();
        $returnRequest->save();
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
}

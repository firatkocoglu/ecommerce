<?php

namespace App\Services\ReturnRequests;

use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
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
     * @throws \JsonException
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


        // Simulate return request creation



    }
}

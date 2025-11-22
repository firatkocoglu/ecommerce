<?php

namespace App\Http\Controllers\API\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Orders\StoreOrderRequest;
use App\Http\Resources\API\V1\Orders\OrderResource;
use App\Services\Orders\OrderService;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index()
    {
        $userId = auth()->user()->id;
        $orders = $this->orderService->listOrdersByUser($userId);

        return OrderResource::collection($orders);
    }

    public function show(int $orderId)
    {
        $userId = auth()->user()->id;
        $order = $this->orderService->getOrderById($orderId, $userId);

        return OrderResource::make($order);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreOrderRequest $request)
    {
        $userId = auth()->user()->id;

        $orderData = [
            'user_id' => $userId,
            'cart_id' => $request->validated()['cart_id'],
            'shipping_address_id' => $request->validated()['shipping_address_id'],
            'billing_address_id' => $request->validated()['billing_address_id'] ?? null,
        ];

        $created = $this->orderService->createOrder($orderData);

        return OrderResource::make($created)->response()->setStatusCode(201);
    }

    /**
     * @throws Throwable
     */
    public function cancel(int $orderId)
    {
        $userId = auth()->user()->id;
        $cancelled = $this->orderService->cancelOrder($userId, $orderId);

        return OrderResource::make($cancelled);
    }
}

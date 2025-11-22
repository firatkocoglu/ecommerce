<?php

namespace App\Http\Controllers\API\V1\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Store\StoreStockRequest;
use App\Services\Stocks\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function store(StoreStockRequest $request): JsonResponse
    {
        $productId = $request->validated()['product_id'];
        $quantity = $request->validated()['quantity'];

        $created = $this->stockService->addStockForProduct($productId, $quantity);

        return response()->json($created, 201);
    }
}

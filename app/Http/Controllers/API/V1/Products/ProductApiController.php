<?php

namespace App\Http\Controllers\API\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Products\ProductResource;
use App\Services\Products\ProductService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\API\V1\Products\StoreProductRequest;
use App\Http\Requests\API\V1\Products\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductApiController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $perPage = request()->integer('per_page', 20);

        $paginator = $this->service->listPaginated($perPage);

        return ProductResource::collection($paginator);
    }

    public function show(int $id): ProductResource
    {
        // Find the product by ID
        // In product service, the existence of given ID will be checked by findOrFail
        $product = $this->service->findById($id);

        return ProductResource::make($product);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $product = $this->service->create($data);
            return ProductResource::make($product)
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            \Log::error('Product create failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }


}

<?php

namespace App\Http\Controllers\API\V1\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Products\ProductResource;
use App\Models\Product;
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
        $perPage = request()->integer('per_page', 10);

        $paginator = $this->service->listPaginated($perPage);

        return ProductResource::collection($paginator);
    }

    public function show(Product $product): ProductResource
    {
        // Find the product by ID
        // In product service, the existence of given ID will be checked by findOrFail
        $productData = $this->service->findById($product);
        return ProductResource::make($productData);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        // Validate the request data
        $data = $request->validated();

        // Create a new product using the service
        $product = $this->service->create($data);
        return ProductResource::make($product)
                ->response()
                ->setStatusCode(201);
    }

    /**
     * @throws Throwable
     */
    public function update(Product $product, UpdateProductRequest $request): ProductResource
    {
        // Validate the request data
        $data = $request->validated();


        // Update the product using the service
        $productData = $this->service->update($product, $data);
        return ProductResource::make($productData);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Product $product): JsonResponse
    {
        // Delete the product using the service
        $this->service->delete($product);
        return response()->json(null, 204);
    }
}

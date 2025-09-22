<?php

namespace App\Http\Controllers\API\V1\ProductVariants;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Variants\StoreVariantRequest;
use App\Http\Requests\API\V1\Variants\UpdateVariantRequest;
use App\Http\Resources\API\V1\Products\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariants\ProductVariantService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class ProductVariantApiController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ProductVariantService $service) {}

    public function index(Product $product): AnonymousResourceCollection
    {
        // List variants by product ID
        $variants = $this->service->listByProductId($product);

        return ProductVariantResource::collection($variants);
    }

    public function show(Product $product, ProductVariant $variant): ProductVariantResource
    {
        // Find the variant by product ID and variant ID
        $variantData = $this->service->findById($product, $variant);

        return ProductVariantResource::make($variantData);
    }

    /**
     * @throws Throwable
     */
    public function store(Product $product, StoreVariantRequest $request): JsonResponse
    {
        // Ensure the user is an admin
        if (! $request->user()?->hasRole('admin', 'admin')) {
            abort(403);
        }

        // Create a new variant using the validated data from the request
        $data = $request->validated();
        $variant = $this->service->create($product, $data);

        return ProductVariantResource::make($variant)->response()->setStatusCode(201);
    }

    /**
     * @throws Throwable
     */
    public function update(Product $product, ProductVariant $variant, UpdateVariantRequest $request): ProductVariantResource
    {
        // Ensure the user is an admin
        if (! $request->user()?->hasRole('admin', 'admin')) {
            abort(403);
        }

        // Update an existing variant using the validated data from the request
        $data = $request->validated();

        $variantData = $this->service->update($product, $variant, $data);

        return ProductVariantResource::make($variantData);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        // Ensure the user is an admin
        if (! $request->user()?->hasRole('admin', 'admin')) {
            abort(403);
        }

        // Delete the variant
        $this->service->delete($product, $variant);

        return response()->json(null, 204);
    }
}

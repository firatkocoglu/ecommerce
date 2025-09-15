<?php

namespace App\Http\Controllers\API\V1\ProductVariants;

use App\Http\Controllers\Controller;
use App\Services\ProductVariants\ProductVariantService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\API\V1\Variants\StoreVariantRequest;
use App\Http\Requests\API\V1\Variants\UpdateVariantRequest;
use App\Http\Resources\API\V1\Products\ProductVariantResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductVariantApiController extends Controller
{
    public function __construct(private readonly ProductVariantService $service) {}

    public function index(): AnonymousResourceCollection
    {
        // Get the product ID from the route parameters
        $productId = request()->route('productId');

        // List variants by product ID
        $variants = $this->service->listByProductId($productId);
        return ProductVariantResource::collection($variants);
    }

    public function show(): ProductVariantResource
    {
        // Get the product ID and variant ID from the route parameters
        $productId = request()->route('productId');
        $variantId = request()->route('variantId');

        // Find the variant by product ID and variant ID
        $variant = $this->service->findById($productId, $variantId);
        return ProductVariantResource::make($variant);
    }
}

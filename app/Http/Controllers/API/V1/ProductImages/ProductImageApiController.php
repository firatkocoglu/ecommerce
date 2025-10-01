<?php

namespace App\Http\Controllers\API\V1\ProductImages;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Images\StoreImageRequest;
use App\Http\Requests\API\V1\Images\UpdateImageRequest;
use App\Http\Resources\API\V1\Products\ProductImageResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductImages\DTO\OwnerContext;
use App\Services\ProductImages\ProductImageService;
use Cloudinary\Api\Exception\ApiError;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductImageApiController extends Controller
{
    public function __construct(private readonly ProductImageService $service) {}

    /**
     * @throws Throwable
     */
    public function store(Product $product, StoreImageRequest $request, ?ProductVariant $variant = null)
    {
        $owner = null;

        if ($variant) {
            $owner = $variant;
        } else {
            $owner = $product;
        }

        $data = $request->validated();
        $imageFile = $request->file('image');

        $image = $this->service->create($owner, $data, $imageFile);

        return ProductImageResource::make($image)->response()->setStatusCode(201);
    }

    /**
     * @throws ApiError
     * @throws Throwable
     */
    public function update(int $productId, int $imageId, UpdateImageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $imageFile = $request->file('image');
        $owner = new OwnerContext('product', $productId);

        $result = $this->service->update($owner, $imageId, $data, $imageFile);

        return response()->json(['image' => new ProductImageResource($result->image), 'siblings_changed' => $result->siblingsChanged])->setStatusCode(200);
    }

    /**
     * @throws ApiError
     * @throws Throwable
     */
    public function updateVariant(int $productId, int $variantId, int $imageId, UpdateImageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $imageFile = $request->file('image');
        $owner = new OwnerContext('variant', $variantId);

        $result = $this->service->update($owner, $imageId, $data, $imageFile);

        return response()->json(['image' => new ProductImageResource($result->image), 'siblings_changed' => $result->siblingsChanged])->setStatusCode(200);
    }

    /**
     * @throws Throwable
     */
    public function destroy(int $productId, int $imageId): JsonResponse
    {
        $owner = new OwnerContext('product', $productId);

        $this->service->delete($owner, $imageId);

        return response()->json(null, 204);
    }

    /**
     * @throws Throwable
     */
    public function destroyVariant(int $productId, int $variantId, int $imageId): JsonResponse
    {
        $owner = new OwnerContext('variant', $variantId);

        $this->service->delete($owner, $imageId);

        return response()->json(null, 204);
    }
}

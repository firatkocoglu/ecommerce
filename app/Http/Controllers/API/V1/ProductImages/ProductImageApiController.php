<?php

namespace App\Http\Controllers\API\V1\ProductImages;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Images\StoreImageRequest;
use App\Http\Resources\API\V1\Products\ProductImageResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductImages\ProductImageService;
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
}

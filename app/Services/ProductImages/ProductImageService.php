<?php

namespace App\Services\ProductImages;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Throwable;

class ProductImageService
{
    /**
     * @throws Throwable
     */
    public function create(Product|ProductVariant $owner, array $data, UploadedFile $file): ProductImage
    {
        // Determine the folder path based on whether the owner is a Product or ProductVariant
        $productType = $owner instanceof Product ? 'product' : 'product_variant';
        $productModel = $owner instanceof Product ? Product::class : ProductVariant::class;
        $folderPath = "ecommerce/$productType/{$owner->id}";

        // Upload the file to Cloudinary
        $upload = Cloudinary::uploadApi()->upload($file->getRealPath(), [
            'folder' => $folderPath,
        ]);

        // Prepare data for the new product image using the upload result
        $response = [
            'public_id' => Arr::get($upload, 'public_id'),
            'width' => Arr::get($upload, 'width'),
            'height' => Arr::get($upload, 'height'),
            'size_bytes' => Arr::get($upload, 'size_bytes'),
            'mime' => $file->getMimeType(),
            'alt_text' => Arr::get($data, 'alt_text'),
            'is_primary' => (bool) Arr::get($data, 'is_primary', false),
        ];

        try {
        // Implementation for creating a new product image
        return DB::transaction(function () use ($owner, $response, $productType, $productModel, $folderPath) {
            // Lock the owner record for update to prevent race conditions
            $lockedProduct = $productModel::whereKey($owner->id)->lockForUpdate()->firstOrFail();

            // Lock the existing images for the owner
            $lockedImage = ProductImage::where($productType . '_id', $lockedProduct->id)
                ->lockForUpdate();

            // Check if there are existing images
            $maxOrder = (clone $lockedImage)->orderBy('sort_order', 'desc')->value('sort_order') ?? 0;

            // Check if any existing image is marked as primary
            $imagesHasPrimary = (clone $lockedImage)->where('is_primary', true)->value('id') !== null;

            // If there are no images yet, or if no primary image exists and the new one isn't marked as primary, set it as primary
            if ( $maxOrder === 0 || (! $imagesHasPrimary && $response['is_primary'] === false) )
            {
                $response['is_primary'] = true;
            }

            // If the new image is marked as primary, unset the primary flag on existing images
            if (Arr::get($response, 'is_primary')) {
                (clone $lockedImage)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Save image record in the database
            $image = ProductImage::create([
               $productType . '_id' => $lockedProduct->id,
                'public_id' => Arr::get($response, 'public_id'),
                'folder' => $folderPath,
                'alt_text' => Arr::get($response, 'alt_text') ?? "{$productType} #{$lockedProduct->id} image",
                'is_primary' => Arr::get($response, 'is_primary', false),
                'sort_order' => $maxOrder + 1,
                'width' => Arr::get($response, 'width'),
                'height' => Arr::get($response, 'height'),
                'mime' => Arr::get($response, 'mime'),
                'size_bytes' => Arr::get($response, 'size_bytes'),
            ]);

            DB::afterCommit(fn () => Cache::tags(['products', 'variants'])->flush());

            return $image;
        });
        }catch (\Throwable $e){
            Cloudinary::uploadApi()->destroy($response['public_id']);
            throw $e;
        }
    }
}

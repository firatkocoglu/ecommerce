<?php

namespace App\Services\ProductImages;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductImages\DTO\UpdateImageResult;
use Cloudinary\Api\Exception\ApiError;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            'size_bytes' => Arr::get($upload, 'bytes'),
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
                $lockedImage = ProductImage::where($productType.'_id', $lockedProduct->id)
                    ->lockForUpdate();

                // Check if there are existing images
                $maxOrder = (clone $lockedImage)->orderBy('sort_order', 'desc')->value('sort_order') ?? 0;

                // Check if any existing image is marked as primary
                $imagesHasPrimary = (clone $lockedImage)->where('is_primary', true)->value('id') !== null;

                // If there are no images yet, or if no primary image exists and the new one isn't marked as primary, set it as primary
                if ($maxOrder === 0 || (! $imagesHasPrimary && $response['is_primary'] === false)) {
                    $response['is_primary'] = true;
                }

                // If the new image is marked as primary, unset the primary flag on existing images
                if (Arr::get($response, 'is_primary')) {
                    (clone $lockedImage)
                        ->where('is_primary', true)
                        ->update(['is_primary' => false]);
                }

                // Save image record in the database
                $image = new ProductImage([
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

                if ($productType === 'product') {
                    $image->product()->associate($lockedProduct)->save();
                } else {
                    $image->productVariant()->associate($lockedProduct)->save();
                }

                DB::afterCommit(fn () => Cache::tags(['products', 'variants'])->flush());

                return $image;
            });
        } catch (\Throwable $e) {
            Cloudinary::uploadApi()->destroy($response['public_id']);
            throw $e;
        }
    }

    /**
     * @throws ApiError
     * @throws Throwable
     */
    public function update(Product|ProductVariant $owner, ProductImage $image, ?array $data, ?UploadedFile $file): UpdateImageResult
    {
        // Determine the folder path based on whether the owner is a Product or ProductVariant
        $productType = $owner instanceof Product ? 'product' : 'product_variant';
        $folderPath = "ecommerce/$productType/{$owner->id}";

        // Determine the model class
        $productModel = $owner instanceof Product ? Product::class : ProductVariant::class;

        $newUpload = null;
        if ($file) {
            $newUpload = Cloudinary::uploadApi()->upload($file->getRealPath(), [
                'folder' => $folderPath,
            ]);
        }

        try {
            return DB::transaction(function () use ($owner, $image, $data, $productType, $newUpload, $productModel, $file, $folderPath) {
                // Lock the owner record for update to prevent
                $baseProduct = $productModel::whereKey($owner->id)->lockForUpdate();
                $lockedProduct = (clone $baseProduct)->firstOrFail();

                // Lock the image record for update
                $lockedImage = ProductImage::where('id', $image->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Sibling images query
                $baseImages = ProductImage::where($productType.'_id', $lockedProduct->id)->lockForUpdate();
                $siblingImages = (clone $baseImages)->whereKeyNot($lockedImage->id);
                $siblingsChanged = false;

                // Obtain existing public ID for deletion if a new image is uploaded
                $oldPublicId = $lockedImage->public_id;

                // Update the image record if a new image file is uploaded
                if ($newUpload) {
                    $lockedImage->fill([
                        'public_id' => Arr::get($newUpload, 'public_id'),
                        'folder' => $folderPath,
                        'width' => Arr::get($newUpload, 'width'),
                        'height' => Arr::get($newUpload, 'height'),
                        'mime' => $file->getMimeType(),
                        'size_bytes' => Arr::get($newUpload, 'bytes'),
                    ]);
                }

                if (! empty($data)) {
                    // Handle updating is_primary flag
                    if (array_key_exists('is_primary', $data)) {
                        if (! $lockedImage->is_primary && $data['is_primary']) {
                            $lockedImage->fill(['is_primary' => true]);

                            // If the new image is marked as primary, unset the primary flag on existing images
                            (clone $siblingImages)->where('is_primary', true)->update(['is_primary' => false]);
                            $siblingsChanged = true;

                        } elseif ($lockedImage->is_primary && ! $data['is_primary']) {
                            // Updating is_primary field can only be true
                            // If trying to set it to false, another image should be set as primary
                            // Reject the change if trying to mark false primary on the current primary image
                            throw ValidationException::withMessages(['is_primary' => 'Cannot unset primary image without assigning another image as primary.']);
                        }
                        // Unset is_primary from data to avoid overwriting
                        unset($data['is_primary']);
                    }

                    // Handle updating sort_order
                    if (array_key_exists('sort_order', $data)) {
                        // Validate sort_order
                        // Sort order must be a positive integer
                        $newSortOrder = (int) $data['sort_order'];
                        $oldSortOrder = $lockedImage->sort_order;

                        // Get the last sort order among images
                        $lastOrder = (clone $baseImages)->orderByDesc('sort_order')->value('sort_order') ?? 0;

                        // Sort order must be a positive integer
                        if ($newSortOrder < 1) {
                            throw ValidationException::withMessages(['sort_order' => 'Sort order must be a positive integer.']);
                        }

                        // Sort order must not exceed the number of sibling images
                        //                        elseif ($newSortOrder > $lastOrder) {
                        //                            throw ValidationException::withMessages(['sort_order' => 'Sort order cannot exceed '.($lastOrder).'.']);
                        //                        }

                        // New sort order is the same as the old one, no need to change
                        elseif ($newSortOrder === $oldSortOrder) {
                            unset($data['sort_order']);
                        }

                        // Adjust sort orders of sibling images
                        else {
                            // Temporarily set the image's sort order to a value outside the current range to avoid unique constraint violations
                            $temp = $lastOrder + 1;
                            $lockedImage->update(['sort_order' => $temp]);
                            // If the new sort order is greater than the old one, decrement sort orders of images between old and new
                            if ($newSortOrder > $oldSortOrder) {
                                for ($i = $oldSortOrder + 1; $i <= $newSortOrder; $i++) {
                                    (clone $siblingImages)->where('sort_order', $i)->decrement('sort_order');
                                }
                            }

                            // If the new sort order is less than the old one, increment sort orders of images between new and old
                            if ($newSortOrder < $oldSortOrder) {
                                for ($i = $oldSortOrder - 1; $i >= $newSortOrder; $i--) {
                                    (clone $siblingImages)->where('sort_order', $i)->increment('sort_order');
                                }
                            }

                            $lockedImage->fill(['sort_order' => $newSortOrder]);
                            $siblingsChanged = true;
                            unset($data['sort_order']);
                        }
                    }

                    // Update other fields
                    $lockedImage->fill(Arr::only($data, ['alt_text']));
                    \Log::log('debug', 'ProductImageService update fill', ['data' => $data, 'image_id' => $lockedImage->id]);
                }

                if (! $lockedImage->isDirty()) {
                    // No changes detected
                    return new UpdateImageResult($lockedImage, false);
                }

                // Save changes to the image record
                $lockedImage->save();

                // If a new image was uploaded, delete the old image from Cloudinary after the transaction commits
                DB::afterCommit(function () use ($newUpload, $oldPublicId) {
                    if ($newUpload) {
                        Cloudinary::uploadApi()->destroy($oldPublicId);
                    }
                    Cache::tags(['products', 'variants'])->flush();
                });

                return new UpdateImageResult($lockedImage, $siblingsChanged);
            });
        } catch (\Throwable $e) {
            if ($newUpload) {
                Cloudinary::uploadApi()->destroy(Arr::get($newUpload, 'public_id'));
            }
            throw $e;
        }
    }
}

<?php

namespace App\Services\ProductImages;

use App\Enums\ImageStatus;
use App\Events\ProductImageDeleted;
use App\Jobs\UploadImageJob;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductImages\DTO\OwnerContext;
use App\Services\ProductImages\DTO\UpdateImageResult;
use Cloudinary\Api\Exception\ApiError;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Storage;
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
        $folderPath = "ecommerce/$productType";

        // Define a temporary path to store the uploaded file before processing
        $tempPath = $file->store('images', 'local');
        $fullPath = Storage::disk('local')->path($tempPath);

        try {
            // Implementation for creating a new product image
            return DB::transaction(function () use ($owner, $data, $folderPath, $fullPath, $productType) {
                // Lock the existing images for the owner
                $lockedImages = ProductImage::where($productType.'_id', $owner->id)
                    ->lockForUpdate()->get(['sort_order', 'is_primary']);

                $maxOrder = (int) $lockedImages->max('sort_order');
                $hasPrimary = (bool) $lockedImages->contains('is_primary', true);
                $isPrimary = (! $hasPrimary);

                // If there are no images yet, or if no primary image exists and the new one isn't marked as primary, set it as primary
                if ($maxOrder === 0 || ! $hasPrimary) {
                    $isPrimary = true;
                }

                // If the new image is marked as primary, unset the primary flag on existing images
                if ($isPrimary) {
                    ProductImage::where($productType.'_id', $owner->id)
                        ->where('is_primary', true)
                        ->update(['is_primary' => false]);
                }

                // Only initialize image storing process with a minimal record and later dispatch a job to upload the image after commit
                $image = new ProductImage([
                    'status' => ImageStatus::PROCESSING,
                    'is_primary' => $isPrimary,
                    'sort_order' => $maxOrder + 1,
                    'alt_text' => $data['alt_text'] ?? null,
                    'folder' => $folderPath,
                ]);

                // Associate the image with the product or variant
                if ($productType === 'product') {
                    $image->product()->associate($owner)->save();
                } else {
                    $image->productVariant()->associate($owner)->save();
                }

                // Prepare extra data to pass to the job
                $extraData = [
                    'imageId' => $image->id,
                    'productType' => $productType,
                    'folderPath' => $folderPath,
                ];

                DB::afterCommit(function () use ($fullPath, $extraData) {
                    // Dispatch the job to upload the image to Cloudinary after the transaction commits
                    UploadImageJob::dispatch($fullPath, $extraData)->onQueue('media');
                    Cache::tags(['products', 'variants'])->flush();
                });

                return $image;
            });
        } catch (\Throwable $e) {
            // If any error occurs, delete the uploaded image from temp folder to avoid orphaned files
            if (! empty($fullPath) && is_file($fullPath)) {
                @unlink($fullPath);
            }
            throw $e;
        }
    }

    /**
     * @throws ApiError
     * @throws Throwable
     */
    public function update(OwnerContext $owner, int $imageId, ?array $data, ?UploadedFile $file): UpdateImageResult
    {
        // Determine the folder path based on whether the owner is a Product or ProductVariant
        $productType = $owner->type;
        $typeId = $owner->id;
        $fk = $owner->fk();
        $folderPath = "ecommerce/{$productType}";

        \Illuminate\Log\log($typeId);

        // Define a temporary path to store the uploaded file before processing
        $tempPath = $file?->store('images', 'local');
        $fullPath = $tempPath ? Storage::disk('local')->path($tempPath) : null;

        // Prepare extra data to pass to the job
        $uploadData = $file ? [
            'imageId' => $imageId,
            'productType' => $productType,
            'folderPath' => $folderPath,
        ] : null;

        try {
            return DB::transaction(function () use ($typeId, $fk, $imageId, $data, $file, $uploadData, $fullPath) {
                // Sibling images query
                $baseImages = ProductImage::where($fk, $typeId)->lockForUpdate();
                $baseCollection = (clone $baseImages)->get();

                $lockedImage = $baseCollection->firstWhere('id', $imageId);
                $siblingsChanged = false;

                if (! empty($data)) {
                    // Handle updating is_primary flag
                    if (array_key_exists('is_primary', $data)) {
                        if (! $lockedImage->is_primary && $data['is_primary']) {
                            $lockedImage->fill(['is_primary' => true]);

                            // If the new image is marked as primary, unset the primary flag on existing images

                            $baseImages->whereKeyNot($imageId)->where('is_primary', true)->update(['is_primary' => false]);
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
                        $lastOrder = $baseCollection->max('sort_order');

                        // Sort order must be a positive integer
                        if ($newSortOrder < 1) {
                            throw ValidationException::withMessages(['sort_order' => 'Sort order must be a positive integer.']);
                        }

                        // New sort order is the same as the old one, no need to change
                        elseif ($newSortOrder === $oldSortOrder) {
                            unset($data['sort_order']);
                        }

                        // Adjust sort orders of sibling images
                        else {
                            // Temporarily set the image's sort order to a value outside the current range to avoid unique constraint violations
                            $temp = $lastOrder + 1000;
                            $lockedImage->update(['sort_order' => $temp]);
                            // If the new sort order is greater than the old one, decrement sort orders of images between old and new
                            if ($newSortOrder > $oldSortOrder) {
                                $baseImages->whereKeyNot($imageId)->whereBetween('sort_order', [$oldSortOrder + 1, $newSortOrder])->decrement('sort_order');
                            }

                            // If the new sort order is less than the old one, increment sort orders of images between new and old
                            if ($newSortOrder < $oldSortOrder) {
                                $baseImages->whereKeyNot($imageId)->whereBetween('sort_order', [$newSortOrder, $oldSortOrder - 1])->increment('sort_order');
                            }

                            $lockedImage->fill(['sort_order' => $newSortOrder]);
                            $siblingsChanged = true;
                            unset($data['sort_order']);
                        }
                    }

                    // Update other fields
                    $lockedImage->fill(Arr::only($data, ['alt_text']));
                }

                if (! $lockedImage->isDirty() && ! $file) {
                    // No changes detected
                    return new UpdateImageResult($lockedImage, false);
                }

                // Save changes to the image record
                $lockedImage->save();

                // If a new image was uploaded, delete the old image from Cloudinary after the transaction commits
                DB::afterCommit(function () use ($file, $fullPath, $uploadData) {
                    if ($file) {
                        // Dispatch the job to upload the image to Cloudinary
                        UploadImageJob::dispatch($fullPath, $uploadData)->onQueue('media');
                    }

                    Cache::tags(['products', 'variants'])->flush();
                });

                return new UpdateImageResult($lockedImage, $siblingsChanged);
            });
        } catch (\Throwable $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function delete(Product|ProductVariant $owner, ProductImage $image): void
    {
        $productType = $owner instanceof Product ? 'product' : 'product_variant';
        $productModel = $owner instanceof Product ? Product::class : ProductVariant::class;

        DB::transaction(function () use ($owner, $image, $productType, $productModel) {
            // Lock the image record for update
            $lockedImage = ProductImage::where('id', $image->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lock the owner record for update to prevent
            $lockedProduct = $productModel::whereKey($owner->id)
                ->lockForUpdate()
                ->firstOrFail();

            $baseImages = ProductImage::where($productType.'_id', $lockedProduct->id)->lockForUpdate();

            // Lock sibling images
            $siblingImages = (clone $baseImages)->whereKeyNot($lockedImage->id);
            $hasSiblings = (clone $siblingImages)->exists();

            // Store the old sort order and old public ID before deletion
            // Arrange sort orders of sibling images
            $oldSortOrder = $lockedImage->sort_order;
            $oldPublicId = $lockedImage->public_id;

            // If the image to be deleted is primary, assign the image with the lowest sort order as primary
            if ($hasSiblings) {
                if ($lockedImage->is_primary) {
                    $newPrimary = (clone $siblingImages)->orderBy('sort_order', 'asc')->value('sort_order') ?? 0;
                    if ($newPrimary) {
                        (clone $siblingImages)->where('sort_order', $newPrimary)->update(['is_primary' => true]);
                    }
                }

                // Get the last sort order among images
                $lastOrder = (clone $baseImages)->orderByDesc('sort_order')->value('sort_order') ?? 0;

                // Temporarily set the image's sort order to a value outside the current range to avoid unique constraint violations
                $temp = $lastOrder + 1;
                $lockedImage->update(['sort_order' => $temp]);

                // Decrement sort orders of images with sort order greater than the deleted image's sort order
                for ($i = $oldSortOrder + 1; $i <= $lastOrder; $i++) {
                    (clone $siblingImages)->where('sort_order', $i)->decrement('sort_order');
                }
            }

            // Delete the image record from the database
            $lockedImage->delete();

            DB::afterCommit(function () use ($oldPublicId) {

                event(new ProductImageDeleted($oldPublicId));
                // Delete the image from Cloudinary after the transaction commits
                Cache::tags(['products', 'variants'])->flush();
            });
        });
    }
}

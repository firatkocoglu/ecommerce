<?php

namespace App\Services\ImageIngestion;

use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImageIngestionService
{
    /**
     * @throws Throwable
     */
    public function countImages(int $productId): int
    {
        return ProductImage::where('product_id', $productId)->count();
    }

    /**
     * @throws Throwable
     */
    public function ingestImages(array $images)
    {
        if (empty($images)) {
            throw new \InvalidArgumentException('Image URL array cannot be empty.', 400);
        }

        $productId = $images['product_id'] ?? null;
        if ($productId === null) {
            throw new \InvalidArgumentException('Product ID is required in image data.', 400);
        }

        return DB::transaction(function () use ($images, $productId) {
            $ingestedImages = [];
            foreach ($images as $index => $image) {
                \Illuminate\Log\log($image);
                // Check how many images already exist for the product
                $numberOfExistingImages = ProductImage::where('product_id', $productId)
                    ->where('product_id', $productId)
                    ->count();

                // Limit to maximum of 4 images per product
                if ($numberOfExistingImages === 4) {
                    break;
                }

                // Skip if the image has already been ingested
                if (in_array($image, $ingestedImages, true)) {
                    continue;
                }

                $isPrimary = false;

                if ($index === 0) {
                    $isPrimary = true;
                }

                $productImage = new ProductImage;
                $productImage->product_id = $productId;
                $productImage->width = $image['width'];
                $productImage->height = $image['height'];
                $productImage->mime = $image['format'];
                $productImage->size_bytes = $image['bytes'];
                $productImage->public_id = $image['public_id'];
                $productImage->is_primary = $isPrimary;
                $productImage->save();

                // Keep track of ingested images to avoid duplicates in the same batch
                $ingestedImages[] = $image;
            }
        });
    }
}

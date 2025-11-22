<?php

namespace App\Http\Controllers\API\V1\ImageIngestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\ImageIngestion\ImageIngestionRequest;
use App\Services\ImageIngestion\ImageIngestionService;
use Throwable;

class ImageIngestionController extends Controller
{
    public function __construct(private readonly ImageIngestionService $imageIngestionService) {}

    /**
     * @throws Throwable
     */
    public function countImages(int $productId): int
    {
        return $this->imageIngestionService->countImages($productId);
    }

    /**
     * @throws Throwable
     */
    public function ingestImages(string $productId, ImageIngestionRequest $request): void
    {
        $data = $request->validated();
        $images = $data['imageData'];
        $images['product_id'] = $productId;
        $this->imageIngestionService->ingestImages($images);
    }
}

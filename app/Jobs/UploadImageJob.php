<?php

namespace App\Jobs;

use App\Enums\ImageStatus;
use App\Models\ProductImage;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class UploadImageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public array $backoff = [5, 30, 120];

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $fullPath,
        public array $data,
    ) {
        $this->afterCommit = true;
        $this->connection = 'redis';
        $this->queue = 'media';
    }

    /**
     * Execute the job.
     *
     * @throws ApiError
     * @throws Throwable
     */
    public function handle(): void
    {
        // Job logic goes here
        // Upload the file to Cloudinary

        // Guard: temp file must exist & be readable
        if (! is_readable($this->fullPath)) {
            $this->fail(new \RuntimeException('Temp image not found or unreadable: '.$this->fullPath));

            return;
        }

        try {
            $upload = new UploadApi()->upload($this->fullPath, [
                'allowed_formats' => ['jpg', 'jpeg', 'png', 'webp'],
                'resource_type' => 'image',
                'folder' => "ecommerce/{$this->data['productType']}",
                'public_id' => (string) $this->data['imageId'],
                'unique_filename' => false,
                'overwrite' => true,
            ]);

            // Define update logic here, e.g., update database record with $upload details
            $data = [
                'public_id' => Arr::get($upload, 'public_id'),
                'width' => Arr::get($upload, 'width'),
                'height' => Arr::get($upload, 'height'),
                'mime' => Arr::get($upload, 'format'),
                'size_bytes' => Arr::get($upload, 'bytes'),
                'status' => ImageStatus::COMPLETED,
            ];

            DB::transaction(function () use ($data) {
                $image = ProductImage::query()->lockForUpdate()->find($this->data['imageId']);
                if ($image) {
                    $image->fill($data);
                    $image->save();
                }
            });

            // Delete the local temp file after successful upload
            if (file_exists($this->fullPath)) {
                unlink($this->fullPath);
            }
        } catch (ApiError $e) {
            try {
                ProductImage::query()
                    ->whereKey($this->data['imageId'])
                    ->update(['status' => ImageStatus::FAILED]);
            } catch (Throwable $innerError) {
                report($innerError);
            }
            throw $e;
        }
    }
}

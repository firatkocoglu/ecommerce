<?php

namespace App\Listeners;

use App\Events\ProductImageDeleted;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class DeleteFromCloudinary implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public string $connection = 'redis';

    public string $queue = 'media'; // Queue for Cloudinary operations

    public int $tries = 5; // Number of attempts

    public array $backoff = [10, 30, 120, 300]; // Backoff times in seconds

    public bool $afterCommit = true; // Ensure the job runs after a successful database transaction

    /**
     * Handle the event.
     *
     * @throws Throwable
     */
    public function handle(ProductImageDeleted $event): void
    {
        try {
            new UploadApi()->destroy($event->publicId, ['invalidate' => true]);
        } catch (Throwable $e) {
            logger()->warning("Couldn't delete image from cloudinary: ", [
                'public_id' => $event->publicId,
                'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // Handle a job failure.
    public function failed(ProductImageDeleted $event): void
    {
        // After all attempts have failed, log the failure
        logger()->error("Couldn't delete image from cloudinary: ", [
            'public_id' => $event->publicId,
            'error' => 'Max attempts reached',
        ]);
    }

    public function tags(): array
    {
        return ['cloudinary', 'media', 'product_image', 'image-deletion'];
    }
}

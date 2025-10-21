<?php

namespace App\Jobs;

use App\Enums\CartStatus;
use App\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeOrderedCarts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Define the threshold date for purging carts older than 30 days
        $threshold = now()->subDays(30);

        $deleted = Cart::where('status', CartStatus::ORDERED->value)
            ->where('expires_at', '<', $threshold)
            ->delete();

        \Log::info("Purge ordered carts for $deleted");
    }
}

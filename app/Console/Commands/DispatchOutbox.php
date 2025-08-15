<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Outbox;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DispatchOutbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:dispatch {--limit=20}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending Outbox events (step by step)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Acquire a short-lived lock so only one dispatcher instance runs at a time
        $lock = Cache::lock('outbox-dispatcher', 55);
        if (! $lock->get()) {
            $this->warn('Another instance is already running. Exiting.');
            return self::SUCCESS;
        }

        try {
            $limit    = (int) $this->option('limit');
            $workerId = (string) Str::uuid();

            $this->info('Dispatching pending Outbox events...');

            // Pull a small batch of pending + due + reservable events, oldest first
            $events = Outbox::pending()
                ->due()
                ->reservable(60)
                ->orderBy('occurred_at')
                ->limit($limit)
                ->get();

            if ($events->isEmpty()) {
                $this->info('No pending Outbox events found.');
                return self::SUCCESS;
            }

            foreach ($events as $event) {
                // Reserve the event so no other worker processes it in parallel
                $event->reserve($workerId, 60);

                try {
                    // Publish (for now just prints to console)
                    $this->publish($event);

                    // Mark success
                    $event->markDispatched();
                    $this->info('Event dispatched successfully.');
                } catch (\Throwable $e) {
                    // Mark failure and schedule a retry with backoff
                    $event->failWithBackoff($e->getMessage());
                    $this->warn('Error: ' . $e->getMessage());
                }
            }

            return self::SUCCESS;
        } finally {
            // Always release the lock, even if we returned early
            $lock->release();
        }
    }

    private function publish(Outbox $event) {
        // Here you would implement the logic to publish the event.
        // This could involve dispatching a job, sending a message to a queue, etc.
        // For example:
        // event(new SomeEvent($event->payload));
        
        // Placeholder for actual publishing logic
        $this->info('Publishing event: ' . $event->event_type->value);

        $this->line(sprintf(
            '→ PUBLISH %s [%s/%s] %s',
            $event->event_type->value,
            $event->aggregate_type->value,
            $event->aggregate_id,
            json_encode($event->payload)
        ));
        
        // Simulate successful publishing
        return true;
    }
}

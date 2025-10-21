<?php

namespace App\Console;

use App\Jobs\PurgeOrderedCarts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Clean up expired admin password reset tokens
        $schedule->call(function () {
            $deleted = DB::table('admin_password_reset_tokens')
                ->where('created_at', '<=', now()->subMinutes(config('auth.passwords.admins.expire', 60)))
                ->delete();

            Log::info("Deleted {$deleted} admin password reset tokens older than ".config('auth.passwords.admins.expire', 60).' minutes.');
        })->daily()->description('Clean up expired admin password reset tokens');

        // Clean up expired user password reset tokens
        $schedule->call(function () {
            $deleted = DB::table('password_reset_tokens')
                ->where('created_at', '<=', now()->subMinutes(config('auth.passwords.users.expire', 60)))
                ->delete();

            Log::info("Deleted {$deleted} user password reset tokens older than ".config('auth.passwords.users.expire', 60).' minutes.');
        })->daily()->description('Clean up expired user password reset tokens');

        // Clean up expired coupons
        $schedule->call(function () {
            $deleted = DB::table('coupons')
                ->where(function ($query) {
                    $query->where('expires_at', '<=', now())
                        ->orWhere('is_active', false);
                })->delete();

            Log::info("Deleted {$deleted} expired coupons.");
        })->daily()->description('Clean up expired coupons');

        // Clean up expired carts
        $schedule->job(new PurgeOrderedCarts)
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->description('Purge ordered carts older than 30 days')
            ->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

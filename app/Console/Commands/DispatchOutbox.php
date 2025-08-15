<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DispatchOutbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:dispatch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch pending Outbox events (step by step)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}

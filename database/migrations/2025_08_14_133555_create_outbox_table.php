<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 50);
            $table->string('aggregate_id', 64);
            $table->string('event_type', 80);
            $table->json('payload');
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamp('dispatched_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->text('error')->nullable();

            $table->timestamp('reserved_at')->nullable();
            $table->string('reserved_by', 64)->nullable();
            $table->timestamp('next_attempt_at')->nullable();

            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index(['dispatched_at', 'occurred_at']);
            $table->index('event_type');
            $table->index('next_attempt_at');

        });

        DB::statement('CREATE INDEX outbox_pending_idx ON outbox (occurred_at) WHERE dispatched_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS outbox_pending_idx');

        Schema::dropIfExists('outbox');
    }
};

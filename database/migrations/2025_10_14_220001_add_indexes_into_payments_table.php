<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_transaction_id ON payments (gateway, transaction_id);
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_provider_event_id ON payments (gateway, provider_event_id) WHERE provider_event_id IS NOT NULL;
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_idempotency_key ON payments (idempotency_key) WHERE idempotency_key IS NOT NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_unique_transaction_id;');
        DB::statement('DROP INDEX IF EXISTS idx_unique_provider_event_id;');
        DB::statement('DROP INDEX IF EXISTS idx_unique_idempotency_key;');
    }
};

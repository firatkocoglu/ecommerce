<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('id');
            $table->string('provider_event_id')->nullable()->after('idempotency_key');
            $table->json('provider_payload')->nullable()->after('provider_event_id');
            $table->decimal('fee_amount', 15, 2)->nullable()->default(0)->after('grand_total');
            $table->decimal('exchange_rate', 15, 8)->nullable()->after('grand_total');
        });

        // Rename column 'amount' to 'grand_total'
        DB::statement('ALTER TABLE payments
            RENAME COLUMN amount TO grand_total;
        ');

        // Rename column 'currency' to 'currency_code'
        DB::statement('ALTER TABLE payments
            RENAME COLUMN currency TO currency_code;
        ');

        // Add 'net_amount' generated column
        DB::statement('ALTER TABLE payments
            ADD COLUMN net_amount DECIMAL(15,2) GENERATED ALWAYS AS (grand_total - fee_amount) STORED;
        ');

        // Add check constraint to 'currency_code' column
        DB::statement('ALTER TABLE payments
            ADD CONSTRAINT currency_code_three_char_uppercase CHECK (currency_code ~ \'^[A-Z]{3}$\');
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'idempotency_key',
                'provider_event_id',
                'provider_payload',
                'fee_amount',
                'exchange_rate',
                'net_amount',
            ]);
        });

        // Rename column 'grand_total' back to 'amount'
        DB::statement('ALTER TABLE payments
            RENAME COLUMN grand_total TO amount;
        ');

        // Rename column 'currency_code' back to 'currency'
        DB::statement('ALTER TABLE payments
            RENAME COLUMN currency_code TO currency;
        ');

        // Drop check constraint from 'currency' column
        DB::statement('ALTER TABLE payments
            DROP CONSTRAINT IF EXISTS currency_code_three_char_uppercase;
        ');
    }
};

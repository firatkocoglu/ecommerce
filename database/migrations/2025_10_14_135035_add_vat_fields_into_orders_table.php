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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->char('currency_code', 3)->default('TRY');
        });

        // Rename total_price to grand_total
        DB::statement('ALTER table orders
            RENAME COLUMN total_price TO grand_total'
        );

        // Add check constraint for currency_code to be exactly 3 uppercase letters
        DB::statement('ALTER TABLE orders
            ADD CONSTRAINT currency_code_three_chars_uppercase
            CHECK (currency_code ~ \'^[A-Z]{3}$\')
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['vat_amount', 'net_total', 'cancelled_at', 'fulfilled_at', 'currency_code']);
        });

        DB::statement('ALTER table orders
            RENAME COLUMN grand_total TO total_price'
        );

        DB::statement('ALTER TABLE orders
        DROP CONSTRAINT IF EXISTS currency_code_three_chars_uppercase');
    }
};

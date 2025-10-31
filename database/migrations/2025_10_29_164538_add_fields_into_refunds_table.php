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
        Schema::table('refunds', function (Blueprint $table) {
           $table->char('currency_code', 3);
           $table->string('idempotency_key')->unique();
           $table->string('provider_refund_id')->nullable()->index();
           $table->string('provider_event_id')->nullable()->index();
           $table->json('provider_payload')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'idempotency_key',
                'provider_refund_id',
                'provider_event_id',
                'provider_payload',
            ]);
        });
    }
};

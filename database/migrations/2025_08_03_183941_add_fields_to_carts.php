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
        Schema::table('carts', function (Blueprint $table) {
            $table->string('session_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('merged_into_cart_id')->nullable()->constrained('carts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
                 $table->dropForeign(['merged_into_cart_id']);
                 $table->dropColumn(['session_id', 'expires_at', 'merged_into_cart_id']);
        });
    }
};

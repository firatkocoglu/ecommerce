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
        Schema::table('product_variants', function (Blueprint $table) {
            // Indicates whether to track stock for this product e.g. some products may not require stock tracking (such as services, digital products, etc.)
            $table->boolean('track_stock')->nullable()->default(true);

            // Indicates whether to allow backorders when stock is insufficient
            $table->boolean('allow_backorders')->nullable()->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['track_stock', 'allow_backorders']);
        });
    }
};

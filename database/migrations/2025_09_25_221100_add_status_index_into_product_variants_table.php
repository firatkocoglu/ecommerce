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
            $table->index('status', 'product_variants_status_index');

            $table->index(['product_id', 'status'], 'product_variants_product_status_index');
        });

        DB::statement("
            CREATE INDEX product_variants_active_index
            ON product_variants (product_id)
            WHERE status = 'active'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_status_index');
            $table->dropIndex('product_variants_product_status_index');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');

            $table->index(['product_variant_id', 'sort_order'], 'product_images_variant_id_sort_order_index');

        });

        DB::statement("
            ALTER TABLE product_images
            ADD CONSTRAINT product_images_owner_xor
            CHECK (
                (product_id IS NOT NULL AND product_variant_id IS NULL) OR
                (product_id IS NULL AND product_variant_id IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_variant_id_sort_order_index');
            $table->dropForeign('product_variant_id');
            $table->dropColumn('product_variant_id');
        });
    }
};

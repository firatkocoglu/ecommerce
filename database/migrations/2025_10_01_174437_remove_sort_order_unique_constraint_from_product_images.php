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
        DROP INDEX IF EXISTS product_images_product_id_sort_order_index;'
        );
        DB::statement('
        DROP INDEX IF EXISTS product_images_variant_id_sort_order_index;'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS product_images_product_id_sort_order_index ON product_images (product_id, sort_order);');
        DB::statement('CREATE INDEX IF NOT EXISTS product_images_variant_id_sort_order_index ON product_images (product_variant_id, sort_order);');
    }
};

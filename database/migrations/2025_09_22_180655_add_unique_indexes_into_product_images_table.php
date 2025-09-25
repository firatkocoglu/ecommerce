<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        DB::statement('
        CREATE UNIQUE INDEX IF NOT EXISTS product_images_product_id_sort_order_index
        ON product_images (product_id, sort_order)
        WHERE product_variant_id IS NULL;
        ');

        DB::statement('
        CREATE UNIQUE INDEX IF NOT EXISTS product_images_variant_id_sort_order_index
        ON product_images (product_variant_id, sort_order)
        WHERE product_id IS NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS product_images_product_id_sort_order_index;');
        DB::statement('DROP INDEX IF EXISTS product_images_variant_id_sort_order_index;');

        DB::statement('CREATE INDEX IF NOT EXISTS product_images_product_id_sort_order_index ON product_images (product_id, sort_order);');
        DB::statement('CREATE INDEX IF NOT EXISTS product_images_variant_id_sort_order_index ON product_images (product_variant_id, sort_order);');
    }
};

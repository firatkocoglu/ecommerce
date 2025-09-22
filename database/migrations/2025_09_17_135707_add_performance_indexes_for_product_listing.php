<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
        CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_products_active_id_cover
        ON products (id)
        INCLUDE (name, price, slug, status)
        WHERE status = 'active'
        ");

        DB::statement('
        CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_catprod_product
        ON category_product (product_id)
        ');

        DB::statement('
        CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_product_variants_product_id_inc
        ON product_variants (product_id)
        INCLUDE (id, sku, price)
        ');

        DB::statement('
        CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_product_images_cover
        ON product_images (product_id, sort_order)
        WHERE product_variant_id IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_products_active_id_cover');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_catprod_product');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_product_variants_product_id_inc');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_product_images_cover');
    }
};

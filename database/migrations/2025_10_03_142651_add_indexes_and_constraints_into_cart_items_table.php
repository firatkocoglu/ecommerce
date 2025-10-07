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
            ALTER TABLE cart_items ADD CONSTRAINT cart_items_quantity_positive
            CHECK (quantity >= 1);
        ');

        DB::statement('
            CREATE UNIQUE INDEX idx_ux_cart_items_cart_product
            ON cart_items (cart_id, product_id)
            WHERE product_variant_id IS NULL;
        ');

        DB::statement('
            CREATE UNIQUE INDEX idx_ux_cart_items_cart_product_variant
            ON cart_items (cart_id, product_id, product_variant_id)
            WHERE product_variant_id IS NOT NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop unique indexes
        DB::statement('DROP INDEX IF EXISTS idx_ux_cart_items_cart_product;');
        DB::statement('DROP INDEX IF EXISTS idx_ux_cart_items_cart_product_variant;');

        // Drop check constraint
        DB::statement('ALTER TABLE cart_items DROP CONSTRAINT IF EXISTS cart_items_quantity_positive;');
    }
};

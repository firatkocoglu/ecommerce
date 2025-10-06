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
        DB::statement(
          '
          DROP INDEX IF EXISTS idx_ux_cart_items_cart_product;
          '
        );

        DB::statement(
            '
            DROP INDEX IF EXISTS idx_ux_cart_items_cart_product_variant;
            '
        );

        DB::statement(
            '
             ALTER TABLE cart_items
             ADD COLUMN variant_key INTEGER GENERATED ALWAYS AS
             (COALESCE(product_variant_id, 0)) STORED;
            '
        );

        DB::statement(
            '
            CREATE UNIQUE INDEX IF NOT EXISTS idx_ux_cart_items_cart_product_variant_key
            ON cart_items (cart_id, product_id, variant_key);
            '
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            '
            DROP INDEX IF EXISTS idx_ux_cart_items_cart_product_variant_key;
            '
        );

        DB::statement(
            '
            ALTER TABLE cart_items
            DROP COLUMN IF EXISTS variant_key;
            '
        );

        DB::statement(
            '
            CREATE UNIQUE INDEX IF NOT EXISTS idx_ux_cart_items_cart_product
            ON cart_items (cart_id, product_id)
            WHERE product_variant_id IS NULL;
            '
        );

        DB::statement(
            '
            CREATE UNIQUE INDEX IF NOT EXISTS idx_ux_cart_items_cart_product_variant
            ON cart_items (cart_id, product_id, product_variant_id)
            WHERE product_variant_id IS NOT NULL;
            '
        );
    }
};

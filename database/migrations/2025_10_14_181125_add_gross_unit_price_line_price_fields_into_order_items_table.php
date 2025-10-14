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
        DB::statement('
            ALTER TABLE order_items
            RENAME COLUMN price TO gross_unit_price;
        ');

        DB::statement('
            ALTER TABLE order_items
            ADD COLUMN subtotal_line_gross DECIMAL(12, 2) GENERATED ALWAYS AS (gross_unit_price * quantity) STORED;
        ');

        DB::statement('
            ALTER TABLE order_items
            ADD COLUMN variant_key BIGINT GENERATED ALWAYS AS (COALESCE(product_variant_id, 0)) STORED;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('
            ALTER TABLE order_items
            DROP COLUMN subtotal_line_gross;
        ');

        DB::statement('
            ALTER TABLE order_items
            DROP COLUMN variant_key;
        ');

        DB::statement('
            ALTER TABLE order_items
            RENAME COLUMN gross_unit_price TO price;
        ');
    }
};

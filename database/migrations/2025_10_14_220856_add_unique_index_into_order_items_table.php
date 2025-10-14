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
            CREATE UNIQUE INDEX IF NOT EXISTS unique_order_id_product_id
                ON order_items (order_id, product_id)
                WHERE variant_key = 0;
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS unique_order_id_product_id_variant_key
                ON order_items (order_id, product_id, variant_key)
                WHERE variant_key > 0;
        ');

        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_order_id ON order_items (order_id);'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unique_order_id_product_id;');
        DB::statement('DROP INDEX IF EXISTS unique_order_id_product_id_variant_key;');
        DB::statement('DROP INDEX IF EXISTS idx_order_id;');
    }
};

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
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Make product_variant_id nullable
        DB::statement('
            ALTER TABLE stocks ALTER COLUMN product_variant_id DROP NOT NULL;
        ');

        // Add a check constraint to ensure only one of product_id or product_variant_id is set
        DB::statement('
            ALTER TABLE stocks ADD CONSTRAINT product_or_variant_only
            CHECK ((product_id IS NOT NULL) <> (product_variant_id IS NOT NULL));
        ');

        DB::statement('
            CREATE UNIQUE INDEX unique_product_where_variant_null
            ON stocks(product_id)
            WHERE product_variant_id IS NULL;
        ');

        DB::statement('
            CREATE UNIQUE INDEX unique_variant_where_variant_not_null
            ON stocks(product_variant_id)
            WHERE product_variant_id IS NOT NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        DB::statement('ALTER TABLE stocks DROP CONSTRAINT product_or_variant_only;');
        DB::statement('DROP INDEX IF EXISTS unique_product_where_variant_null;');
        DB::statement('DROP INDEX IF EXISTS unique_variant_where_variant_not_null;');

        DB::statement('ALTER TABLE stocks ALTER COLUMN product_variant_id SET NOT NULL;');
    }
};

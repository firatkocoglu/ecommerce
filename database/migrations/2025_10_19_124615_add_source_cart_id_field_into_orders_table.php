<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->bigInteger('source_cart_id')->unsigned()->nullable();
        });

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS unique_source_cart_id_index
                    ON orders (source_cart_id)
                    WHERE status=\'pending\''
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('source_cart_id');
        });

        DB::statement(
            'DROP INDEX IF EXISTS unique_source_cart_id_index'
        );
    }
};

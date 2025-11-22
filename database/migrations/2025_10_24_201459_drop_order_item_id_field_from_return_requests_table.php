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
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn('order_item_id');
            $table->timestamp('rejected_at')->nullable()->after('processed_at');
            $table->string('rma_number')->unique()->nullable()->after('id');
            // Rename processed_at to approved_at
            DB::statement('
                ALTER TABLE return_requests
                RENAME COLUMN processed_at TO approved_at;
            ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('order_item_id')->nullable()->after('order_id');
            $table->dropColumn('rejected_at');

            // Rename approved_at back to processed_at
            DB::statement('
                ALTER TABLE return_requests
                RENAME COLUMN approved_at TO processed_at;
            ');
        });
    }
};

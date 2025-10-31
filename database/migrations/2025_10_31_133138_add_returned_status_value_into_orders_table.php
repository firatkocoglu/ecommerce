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
            ALTER TABLE orders
            DROP CONSTRAINT IF EXISTS orders_status_check;
        ');

        DB::statement('
            ALTER TABLE orders
            ADD CONSTRAINT orders_status_check
            CHECK (status IN (\'pending\', \'processing\', \'completed\', \'cancelled\', \'refunded\', \'returned\'));
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

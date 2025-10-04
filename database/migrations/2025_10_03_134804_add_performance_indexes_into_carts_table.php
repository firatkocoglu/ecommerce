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
        DB::statement("
            CREATE UNIQUE INDEX idx_ux_carts_user_active
            ON carts (user_id)
            WHERE status = 'active' and user_id IS NOT NULL
            "
        );

        DB::statement(
            "
            CREATE INDEX idx_carts_status_updated_at
            ON carts (status, updated_at);
            "
        );

        // Ensure that active carts have either user_id or cart_token, but not both or neither
        DB::statement(
            "
             ALTER TABLE carts ADD CONSTRAINT active_carts_user_xor_cart_token
             CHECK (
                 status <> 'active' OR ((user_id IS NULL) <> (cart_token IS NULL)));
             "
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS idx_ux_carts_user_active");
        DB::statement("DROP INDEX IF EXISTS idx_carts_status_updated_at");
        DB::statement("ALTER TABLE carts DROP CONSTRAINT IF EXISTS active_carts_user_xor_cart_token");
    }
};

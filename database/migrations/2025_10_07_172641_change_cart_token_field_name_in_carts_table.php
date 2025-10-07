<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            ALTER TABLE carts
            RENAME COLUMN "cart_token" TO "cart_token_hash";
        ');

        DB::statement('
            ALTER TABLE carts
            ALTER COLUMN "cart_token_hash" TYPE CHAR(64);
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_cart_token_hash
            ON carts (cart_token_hash)
            WHERE cart_token_hash IS NOT NULL;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_unique_cart_token_hash');
        DB::statement('
                ALTER TABLE carts
                ALTER COLUMN "cart_token_hash" TYPE uuid;
            ');
        DB::statement('
                ALTER TABLE carts
                RENAME COLUMN "cart_token_hash" TO "cart_token";
            ');
    }
};

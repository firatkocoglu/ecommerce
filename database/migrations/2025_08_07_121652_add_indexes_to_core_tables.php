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
        Schema::table('users', function (Blueprint $table) {
            $table->index(['created_at'], 'users_created_at_index');
            $table->index(['updated_at'], 'users_updated_at_index');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->index(['user_id'], 'carts_user_id_index');
            $table->index(['session_id'], 'carts_session_id_index');
            $table->index(['merged_into_cart_id'], 'carts_merged_into_cart_id_index');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id'], 'cart_items_cart_id_index');
            $table->index(['product_variant_id'], 'cart_items_product_variant_id_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id'], 'orders_user_id_index');
            $table->index(['status'], 'orders_status_index');
            $table->index(['created_at'], 'orders_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id'], 'payments_user_id_index');
            $table->index(['order_id'], 'payments_order_id_index');
            $table->index(['status'], 'payments_status_index');
            $table->index(['transaction_id'], 'payments_transaction_id_index');
            $table->index(['payment_method'], 'payments_payment_method_index');
            $table->index(['created_at'], 'payments_created_at_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id'], 'products_category_id_index');
            $table->index(['slug'], 'products_slug_index');
            $table->index(['status'], 'products_status_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id'], 'product_variants_product_id_index');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->index(['code'], 'coupons_code_index');
            $table->index(['expires_at'], 'coupons_expires_at_index');
            $table->index(['is_active'], 'coupons_status_index');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->index(['user_id'], 'refunds_user_id_index');
            $table->index(['order_id'], 'refunds_order_id_index');
            $table->index(['status'], 'refunds_status_index');
            $table->index(['created_at'], 'refunds_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_created_at_index');
            $table->dropIndex('users_updated_at_index');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropIndex('carts_user_id_index');
            $table->dropIndex('carts_session_id_index');
            $table->dropIndex('carts_merged_into_cart_id_index');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('cart_items_cart_id_index');
            $table->dropIndex('cart_items_product_variant_id_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_user_id_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_user_id_index');
            $table->dropIndex('payments_order_id_index');
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_transaction_id_index');
            $table->dropIndex('payments_payment_method_index');
            $table->dropIndex('payments_created_at_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_category_id_index');
            $table->dropIndex('products_slug_index');
            $table->dropIndex('products_status_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('product_variants_product_id_index');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropIndex('coupons_code_index');
            $table->dropIndex('coupons_expires_at_index');
            $table->dropIndex('coupons_status_index');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex('refunds_user_id_index');
            $table->dropIndex('refunds_order_id_index');
            $table->dropIndex('refunds_status_index');
            $table->dropIndex('refunds_created_at_index');
        });
    }
};

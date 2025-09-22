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
        Schema::table('product_images', function (Blueprint $table) {
            if (Schema::hasColumn('product_images', 'url')) {
                $table->dropColumn('url');
            }

            // Cloudinary fields
            $table->string('public_id')->unique()->after('product_variant_id');
            $table->string('folder')->nullable()->after('public_id');

            // Additional metadata fields
            $table->string('alt_text')->nullable()->after('folder');
            $table->unsignedInteger('width')->nullable()->after('alt_text');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->string('mime', 100)->nullable()->after('height');
            $table->unsignedInteger('size_bytes')->nullable()->after('mime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

<?php

namespace Database\Seeders;

use App\Models\ProductImage;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::chunk(100, function ($products) {
            foreach($products as $product){
            $imageCount = fake()->numberBetween(1, 5);
            $isFirst = ! $product->images()->where('is_primary', true)->exists();

            ProductImage::factory()
                ->count($imageCount)
                ->for($product)
                ->make()
                ->each(function ($image) use (&$isFirst) {
                    $image->is_primary = $isFirst; // Set first image as primary
                    $image->save();
                    $isFirst = false; // Subsequent images are not primary
                });
            }
        });    
    }
}

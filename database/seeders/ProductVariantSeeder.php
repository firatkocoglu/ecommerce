<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ProductVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $variantCount = fake()->numberBetween(1, 3);

            ProductVariant::factory()
                ->count($variantCount)
                ->for($product)
                ->make()
                ->each(function ($variant) use ($product) {
                    $variant->price = $product->price + rand(0, 5000);
                    $variant->product_id = $product->id;
                    $variant->save();
                });
        }
    }
}

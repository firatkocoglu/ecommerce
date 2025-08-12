<?php

namespace Database\Seeders;

use App\Models\Stock;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        ProductVariant::chunk(100, function ($variants) {
            $rows = [];

            foreach($variants as $variant) {
                $rows[] = [
                    'product_variant_id' => $variant->id,
                    'quantity' => fake()->numberBetween(0,100),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Stock::upsert($rows, ['product_variant_id'], ['quantity', 'updated_at']);
        });
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'color' => fake()->colorName(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'weight' => fake()->numberBetween(100, 5000),
            'sku' => fake()->unique()->bothify('SKU-###??'),
            'created_at' => now(),
        ];
    }
}

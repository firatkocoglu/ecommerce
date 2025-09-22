<?php

namespace Tests\Feature\Api\V1\Products;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin', 'admin');

        if (app()->environment('testing')) {
            config(['database.connections.pgsql.password' => env('DB_PASSWORD')]);
        }

        // Create a category for testing
        $this->category = Category::factory()->create();

        // Create an admin user and get token
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('admin');

        $this->token = $this->admin->createToken('test-token', ['*'])->plainTextToken;
    }

    public function test_public_can_list_products(): void
    {
        Product::factory()->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'status',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_get_one_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'description',
                'price',
                'weight',
                'status',
            ],
        ]);
    }

    public function test_show_returns_404_when_product_not_found(): void
    {
        $response = $this->getJson('/api/v1/products/999999999');

        $response->assertStatus(404);
    }

    public function test_admin_can_store_product(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'weight' => 1.5,
            'status' => 'active',
            'categories' => [$this->category->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'weight' => 1.5,
            'status' => 'active',
        ]);
    }

    public function test_category_product_relation_exists(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->postJson('/api/v1/products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'weight' => 1.5,
            'status' => 'active',
            'categories' => [$this->category->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('category_product', [
            'category_id' => $this->category->id,
            'product_id' => $response->json('data.id'),
        ]);
    }

    public function test_non_admin_cannot_store_product(): void
    {
        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'weight' => 1.5,
            'status' => 'active',
            'categories' => [$this->category->id],
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('products', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'description' => 'This is a new product',
            'price' => 99.99,
            'weight' => 1.5,
            'status' => 'active',
        ]);

    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
            'categories' => [$this->category->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
        ]);
    }

    public function test_cannot_update_non_existent_product(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson('/api/v1/products/999999999', [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
            'categories' => [$this->category->id],
        ]);

        $response->assertStatus(404);
    }

    public function test_patch_without_categories_keeps_existing_relations(): void
    {
        $product = Product::factory()->create();
        $product->categories()->attach($this->category->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
            // No categories provided
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('category_product', [
            'category_id' => $this->category->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_patch_with_categories_updates_relations(): void
    {
        $product = Product::factory()->create();
        $product->categories()->attach($this->category->id);

        $newCategory = Category::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
            'categories' => [$newCategory->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('category_product', [
            'category_id' => $newCategory->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('category_product', [
            'category_id' => $this->category->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_patch_with_empty_categories_array_clears_relations(): void
    {
        $product = Product::factory()->create();
        $product->categories()->attach($this->category->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->patchJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'description' => 'This is an updated product',
            'price' => 79.99,
            'weight' => 1.8,
            'status' => 'active',
            'categories' => [], // Empty array to clear relations
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('category_product', [
            'category_id' => $this->category->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson("/api/v1/products/{$product->id}");
        $response->assertStatus(204);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_non_admin_cannot_delete_product(): void
    {
        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $product = Product::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_cannot_delete_non_existent_product(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
        ])->deleteJson('/api/v1/products/999999999');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature\Api\V1\ProductVariants;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductVariantApiTest extends TestCase
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

        // Create a product for testing
        $this->product = Product::factory()->create();

        // Create an admin user and get token
        $this->admin = Admin::factory()->create();
        $this->admin->assignRole('admin');

        $this->token = $this->admin->createToken('test-token', ['*'])->plainTextToken;

    }

    public function test_public_can_list_product_variants()
    {
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        $url = route('api.v1.variants.index', ['product' => $this->product->id]);

        $response = $this->getJson($url);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'sku',
                    'price',
                    'color',
                    'size',
                    'weight',
                    'images',
                ],
            ],
        ]);
    }

    public function test_public_can_show_product_variant()
    {
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        $response = $this->getJson("/api/v1/products/{$this->product->id}/variants/{$variant->json('data.id')}");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'product_id',
                'price',
                'sku',
                'color',
                'size',
                'weight',
                'images',
            ],
        ]);
    }

    public function test_show_returns_404_for_nonexistent_variant()
    {
        $response = $this->getJson("/api/v1/products/{$this->product->id}/variants/99999");
        $response->assertStatus(404);
    }

    public function test_admin_can_store_product_variant()
    {

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('product_variants', [
            'id' => $response->json('data.id'),
            'product_id' => $this->product->id,
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'weight' => 0.5,
            'color' => 'Red',
            'size' => 'M',
            'status' => 'active',
        ]);
    }

    public function test_non_admin_cannot_store_product_variant()
    {
        $non_admin = Admin::factory()->create();
        // Not assigning admin role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_product_variant()
    {
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants/", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->patchJson("/api/v1/products/{$this->product->id}/variants/{$variant->json('data.id')}", [
            'price' => 59.99,
            'color' => 'Blue',
            'size' => 'L',
            'weight' => 0.6,
            'status' => 'draft',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->json('data.id'),
            'product_id' => $this->product->id,
            'sku' => 'SKU123-XYZ',
            'price' => 59.99,
            'color' => 'Blue',
            'size' => 'L',
            'weight' => 0.6,
            'status' => 'draft',
        ]);
    }

    public function test_admin_cannot_update_nonexistent_variant()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->patchJson("/api/v1/products/{$this->product->id}/variants/99999", [
            'price' => 59.99,
            'color' => 'Blue',
            'size' => 'L',
            'weight' => 0.6,
            'status' => 'draft',
        ]);

        $response->assertStatus(404);
    }

    public function test_non_admin_cannot_update_product_variant()
    {
        // First, create a variant as admin
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        // Create a non-admin user
        $nonAdmin = User::factory()->create();
        $this->actingAs($nonAdmin, 'sanctum');

        $this->assertFalse($nonAdmin->hasRole('admin'));

        $url = route('api.v1.variants.update', [
            'product' => $this->product->id,
            'variant' => $variant->json('data.id'),
        ]);

        $response = $this->patchJson($url, [
            'price' => 59.99,
            'color' => 'Blue',
            'size' => 'L',
            'weight' => 0.6,
            'status' => 'draft',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_product_variant()
    {
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/v1/products/{$this->product->id}/variants", [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson("/api/v1/products/{$this->product->id}/variants/{$variant->json('data.id')}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('product_variants', [
            'id' => $variant->json('data.id'),
        ]);
    }

    public function test_non_admin_cannot_delete_product_variant()
    {
        $storeUrl = route('api.v1.variants.store', ['product' => $this->product->id]);
        // First, create a variant as admin
        $variant = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson($storeUrl, [
            'sku' => 'SKU123-XYZ',
            'price' => 49.99,
            'color' => 'Red',
            'size' => 'M',
            'weight' => 0.5,
            'status' => 'active',
        ]);

        $variant->assertStatus(201);

        // Create a non-admin user
        $nonAdmin = User::factory()->create();
        // Not assigning admin role
        $this->assertFalse($nonAdmin->hasRole('admin'));

        $this->actingAs($nonAdmin, 'sanctum');

        // Now attempt to delete as non-admin
        $url = route('api.v1.variants.destroy', [
            'product' => $this->product->id,
            'variant' => $variant->json('data.id'),
        ]);

        $response = $this->deleteJson($url);

        $response->assertStatus(403);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->json('data.id'),
        ]);
    }
}

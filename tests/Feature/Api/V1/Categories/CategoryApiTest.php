<?php

namespace Tests\Feature\Api\V1\Categories;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CategoryApiTest extends TestCase
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
    }

    public function test_public_can_list_categories_index()
    {
        $root = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);
        Category::factory()->create(['name' => 'Gaming Laptops', 'slug' => 'gaming-laptops', 'parent_id' => $root->id]);
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()->assertJson(fn (AssertableJson $json) => $json->has('data')
            ->has('data.0', fn (AssertableJson $j) => $j->hasAll(['id', 'name', 'slug', 'parent', 'children_count'])
            )
            ->has('links')
            ->has('meta')
        );
    }

    public function test_show_returns_404_when_category_not_found()
    {
        $response = $this->getJson('/api/v1/categories/99999');

        $response->assertNotFound();
    }

    public function test_admin_can_create_category()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->postJson('/api/v1/categories', [
                'name' => 'New Category',
                'slug' => 'new-category',
                'parent_id' => null,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);
    }

    public function test_non_admin_cannot_create_category()
    {
        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->postJson('/api/v1/categories', [
                'name' => 'New Category',
                'slug' => 'new-category',
                'parent_id' => null,
            ]);

        $this->assertDatabaseMissing('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $response->assertForbidden();
    }

    public function test_store_rejects_duplicate_slug()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        Category::factory()->create([
            'name' => 'Unique Category',
            'slug' => 'unique-category',
            'parent_id' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->postJson('/api/v1/categories', [
                'name' => 'Another Category',
                'slug' => 'unique-category',
                'parent_id' => null,
            ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_update_category()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->patchJson("api/v1/categories/$category->id", [
                'name' => 'Updated Category',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'name' => 'Updated Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);
    }

    public function test_update_rejects_duplicate_slug()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $category = Category::factory()->create([
            'name' => 'Unique Category',
            'slug' => 'unique-category',
            'parent_id' => null,
        ]);

        Category::factory()->create([
            'name' => 'Another Category',
            'slug' => 'another-category',
            'parent_id' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->patchJson("/api/v1/categories/$category->id", [
                'slug' => 'another-category',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['slug']);
    }

    public function test_non_admin_cannot_update_category()
    {
        // Create a category to delete
        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);

        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->patchJson("/api/v1/categories/$category->id");

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);
    }

    public function test_admin_can_delete_category()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->deleteJson("/api/v1/categories/$category->id");

        $response->assertNoContent();
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_delete_rejects_category_with_children()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $parentCategory = Category::factory()->create([
            'name' => 'Parent Category',
            'slug' => 'parent-category',
            'parent_id' => null,
        ]);

        Category::factory()->create([
            'name' => 'Child Category',
            'slug' => 'child-category',
            'parent_id' => $parentCategory->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->deleteJson('/api/v1/categories/'.$parentCategory->id);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', fn ($m) => str_contains($m, 'Cannot delete'));
        $this->assertDatabaseHas('categories', [
            'id' => $parentCategory->id,
        ]);
    }

    public function test_throttle_limits_requests()
    {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        for ($i = 0; $i <= 20; $i++) {
            $response = $this->withHeaders([
                'Authorization' => "Bearer $token",
            ])
                ->postJson('/api/v1/categories', [
                    'name' => Str::random(8),
                    'slug' => Str::lower(Str::random(8)),
                    'parent_id' => null,
                ]);

            if ($i < 20) {
                $response->assertCreated();
            } else {
                $response->assertStatus(429);
                $response->assertJsonPath('message', fn ($m) => str_contains($m, 'Too Many'));
            }
        }
    }

    public function test_non_admin_cannot_delete_category()
    {
        // Create a category to delete
        $category = Category::factory()->create([
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);

        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])
            ->deleteJson("/api/v1/categories/$category->id");

        $response->assertForbidden();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Category',
            'slug' => 'category',
            'parent_id' => null,
        ]);
    }
}

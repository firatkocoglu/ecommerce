<?php

namespace Tests\Feature\Api\V1\Categories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Category;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void{
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Role::findOrCreate('admin', 'admin');

        if (app()->environment('testing')) {
            config(['database.connections.pgsql.password' => env('DB_PASSWORD')]);   
        }
    }

    public function test_public_can_list_categories_index() {
        $root = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);
        Category::factory()->create(['name' => 'Gaming Laptops', 'slug' => 'gaming-laptops', 'parent_id' => $root->id]);
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()->assertJson(fn(AssertableJson $json) => 
            $json->has('data')
            ->has('data.0', fn(AssertableJson $j) =>
                $j->hasAll(['id', 'name', 'slug', 'parent', 'children_count'])
            )
            ->has('links')
            ->has('meta')
        );
    }

    public function test_show_returns_404_when_category_not_found() {
        $response = $this->getJson('/api/v1/categories/99999'); 

        $response->assertNotFound();
    }
    
    public function test_admin_can_create_category() {
        $admin = Admin::factory()->create();
        $admin->assignRole('admin');

        $token = $admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])
        ->postJson('/api/v1/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'parent_id' => null
        ]);

        dump('Response status:', $response->status());
        dump('Response content:', $response->content());

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
             'name' => 'New Category',
             'slug' => 'new-category'
         ]);
    }

    public function test_non_admin_cannot_create_category() {
        $non_admin = Admin::factory()->create();
        // We didn't assign role

        $token = $non_admin->createToken('test-token', ['*'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])
        ->postJson('/api/v1/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'parent_id' => null
        ]);

        $this->assertDatabaseMissing('categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $response->assertForbidden();
    }
 }

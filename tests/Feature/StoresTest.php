<?php

namespace Tests\Feature;

use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Stores\Models\Store;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoresTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        (require app_path('Modules/Admin/Stores/Migrations/2026_10_10_082900_create_stores_table.php'))->up();
        (require app_path('Modules/Admin/Products/Migrations/2023_01_07_130922_create_products_table.php'))->up();
        (require app_path('Modules/Admin/Products/Migrations/2026_10_10_083000_add_store_id_to_products_table.php'))->up();
        Schema::create('categories', fn (Blueprint $table) => $table->increments('id'));
        DB::table('categories')->insert(['id' => 1]);
    }

    private function signIn(array $permissions): void
    {
        $user = new class extends User
        {
            public array $testPermissions = [];

            public function canDo($alias, $require = false)
            {
                return count(array_intersect((array) $alias, $this->testPermissions)) > 0;
            }
        };
        $user->id = 1;
        $user->testPermissions = $permissions;
        $this->actingAs($user, 'api');
    }

    public function test_store_lcud_and_product_link(): void
    {
        $this->signIn(['STORE_ACCESS', 'STORE_MANAGE']);
        $response = $this->postJson('/api/admin/stores', ['title' => 'Main']);
        $response->assertOk()->assertJsonPath('data.item.title', 'Main');
        $id = $response->json('data.item.id');

        $this->getJson('/api/admin/stores/'.$id)->assertOk()->assertJsonPath('data.item.title', 'Main');
        $this->putJson('/api/admin/stores/'.$id, ['title' => 'Updated'])
            ->assertOk()->assertJsonPath('data.item.title', 'Updated');

        $product = Product::create(['title' => 'Chair', 'store_id' => $id]);
        $this->assertSame('Updated', $product->store->title);
        $this->assertSame($product->id, Store::findOrFail($id)->products()->first()->id);

        $this->deleteJson('/api/admin/stores/'.$id)->assertOk();
        $this->assertDatabaseMissing('stores', ['id' => $id]);
        $this->assertNull($product->fresh()->store_id);
        $this->getJson('/api/admin/stores/'.$id)->assertNotFound();
    }

    public function test_list_filters_sorts_and_paginates_without_subdivision(): void
    {
        $this->signIn(['STORE_ACCESS']);
        Store::insert([['title' => 'Main A'], ['title' => 'Other'], ['title' => 'Main B']]);

        $this->getJson('/api/admin/stores?title=Main&sort=-title&per_page=1&page=2')
            ->assertOk()->assertJsonPath('data.items.total', 2)
            ->assertJsonPath('data.items.current_page', 2)
            ->assertJsonPath('data.items.data.0.title', 'Main A');
        $this->getJson('/api/admin/stores?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/admin/stores?page_size=101')->assertUnprocessable();
    }

    public function test_routes_enforce_view_and_manage_permissions(): void
    {
        $store = Store::create(['title' => 'Main']);
        $this->signIn([]);
        $this->getJson('/api/admin/stores')->assertForbidden();
        $this->getJson('/api/admin/stores/'.$store->id)->assertForbidden();
        $this->signIn(['STORE_ACCESS']);
        $this->postJson('/api/admin/stores', ['title' => 'New'])->assertForbidden();
        $this->putJson('/api/admin/stores/'.$store->id, ['title' => 'New'])->assertForbidden();
        $this->deleteJson('/api/admin/stores/'.$store->id)->assertForbidden();
        $this->signIn(['STORE_MANAGE']);
        $this->postJson('/api/admin/stores', [])->assertUnprocessable();
        $this->postJson('/api/admin/stores', ['title' => str_repeat('a', 256)])->assertUnprocessable();
        $this->postJson('/api/admin/stores', ['title' => 'New'])->assertOk();
        $this->signIn(['SUPER_ADMINISTRATOR']);
        $this->getJson('/api/admin/stores')->assertOk();
    }

    public function test_product_validates_optional_store_and_migration_rolls_back(): void
    {
        $store = Store::create(['title' => 'Main']);
        $rules = (new ProductsRequest())->rules();
        $data = ['title' => 'Chair', 'quantity' => 1, 'category_id' => 1];
        $this->assertTrue(Validator::make($data, $rules)->passes());
        $this->assertTrue(Validator::make($data + ['store_id' => null], $rules)->passes());
        $this->assertTrue(Validator::make($data + ['store_id' => $store->id], $rules)->passes());
        $this->assertFalse(Validator::make($data + ['store_id' => 999], $rules)->passes());

        (require app_path('Modules/Admin/Products/Migrations/2026_10_10_083000_add_store_id_to_products_table.php'))->down();
        $this->assertFalse(Schema::hasColumn('products', 'store_id'));
    }
}

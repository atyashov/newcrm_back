<?php

namespace Tests\Feature;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\Outlays\Requests\DeleteOutlayRequest;
use App\Modules\Admin\Outlays\Requests\ListOutlaysRequest;
use App\Modules\Admin\Outlays\Requests\OutlayRequest;
use App\Modules\Admin\Outlays\Services\OutlayService;
use App\Modules\Admin\Products\Models\Product;
use App\Modules\Admin\Products\Requests\BatchAvailabilityRequest;
use App\Modules\Admin\Products\Requests\ProductsRequest;
use App\Modules\Admin\Products\Services\ProductsService;
use App\Modules\Admin\User\Models\User;
use App\Modules\Admin\Warehouses\Models\ProductRental;
use App\Modules\Admin\Warehouses\Services\WarehouseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutlayCompositionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['users', 'subdivisions', 'statuses', 'client_types', 'sources', 'subjects', 'payment_methods'] as $table) {
            Schema::create($table, fn (Blueprint $t) => $t->id());
            DB::table($table)->insert(['id' => 1]);
        }
        DB::table('subdivisions')->insert(['id' => 2]);
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('status')->default('1');
        });
        Schema::table('subdivisions', function (Blueprint $table) {
            $table->string('title')->default('Subdivision');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('site_link')->nullable();
        });
        Schema::table('statuses', function (Blueprint $table) {
            $table->string('title')->default('New');
        });
        Schema::create('products', function (Blueprint $t) {
            $t->increments('id');
            $t->string('title');
            $t->string('image')->nullable();
            $t->boolean('is_rent');
            $t->boolean('unlimited')->default(false);
            $t->boolean('is_service')->default(false);
            $t->decimal('quantity', 12, 3)->default(10);
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('subdivision_id');
        });
        Schema::create('category_product', function (Blueprint $t) {
            $t->unsignedInteger('category_id');
            $t->unsignedInteger('product_id');
        });
        Schema::create('product_description', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('product_id');
            $t->text('description');
        });
        DB::table('products')->insert(['id' => 1, 'title' => 'Chair', 'image' => '/chair.jpg', 'is_rent' => true]);
        DB::table('categories')->insert(['id' => 1, 'subdivision_id' => 1]);
        DB::table('category_product')->insert(['category_id' => 1, 'product_id' => 1]);
        DB::table('product_description')->insert(['product_id' => 1, 'description' => 'Catalog description']);

        foreach ([
            'Stores/Migrations/2026_10_10_082900_create_stores_table.php',
            'Subdivisions/Migrations/2026_03_22_045705_create_users_subdivisions_table.php',
            'Products/Migrations/2026_10_10_083000_add_store_id_to_products_table.php',
            'CashBoxes/Migrations/2026_07_26_132355_create_cash_boxes_table.php',
            'OutlayGroupNames/Migrations/2023_08_28_090538_create_outlay_group_names_table.php',
            'Outlays/Migrations/2022_12_17_082119_create_outlays_table.php',
            'Outlays/Migrations/2026_09_26_000001_add_prepayment_fields_to_outlays_table.php',
            'OutlayGroups/Migrations/2026_08_16_084039_create_outlay_groups_table.php',
            'OutlayGroups/Migrations/2026_09_21_000001_normalize_outlay_composition.php',
            'OutlayGroups/Migrations/2026_10_17_000002_create_outlay_item_stages.php',
            'OutlayGroups/Migrations/2026_10_20_000001_add_outlay_work_notes_and_item_comments.php',
            'Statuses/Migrations/2026_09_26_000002_add_affects_stock_to_statuses_table.php',
            'CashBoxes/Migrations/2026_09_26_000004_create_cash_ledger.php',
            'Outlays/Migrations/2026_09_26_000005_add_cash_boxes_to_outlays_table.php',
            'Outlays/Migrations/2026_10_17_000001_add_outlay_dispatch_fields.php',
            'Outlays/Migrations/2026_10_19_000001_add_public_uuid_to_outlays.php',
            'Outlays/Migrations/2026_10_20_000002_create_outlay_audit_events.php',
            'Warehouses/Migrations/2026_09_26_000003_create_warehouse_tables.php',
        ] as $path) {
            (require app_path('Modules/Admin/'.$path))->up();
        }
        DB::table('outlay_group_names')->insert(['id' => 1, 'name' => 'Hall', 'subdivision_id' => 1]);
        DB::table('cash_boxes')->insert([
            'id' => 1, 'title' => 'Main', 'subdivision_id' => 1, 'balance' => 0,
            'opening_balance' => 0, 'payment_method_id' => 1, 'sort_order' => 1,
            'is_dashboard' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'title' => 'Event', 'execution_date' => '2026-10-01 10:00:00',
            'return_date' => '2026-10-02 18:00:00', 'subdivision_id' => 1,
            'creator_id' => 1, 'responsible_workers' => [1], 'status_id' => 1,
            'client_id' => 1, 'partner_entity' => 'individual',
            'groups' => [['outlay_group_name_id' => 1, 'items' => [
                ['product_id' => 1, 'price' => '15.00', 'quantity' => '1'],
                ['product_id' => 1, 'price' => '3.00', 'quantity' => '1'],
            ]]],
        ];
    }

    private function signInForOutlays(bool $manage = true, int $userId = 1): void
    {
        $user = new class extends User
        {
            public bool $manage = true;

            public function canDo($alias, $require = false)
            {
                return count(array_intersect((array) $alias, $this->manage ? ['OUTLAYS_ACCESS', 'OUTLAYS_MANAGE'] : ['OUTLAYS_ACCESS'])) > 0;
            }

            public function getAvailableSubdivisionIds(): array
            {
                return [1];
            }

            public function hasSubdivisionAccess(int|string|null $id): bool
            {
                return (int) $id === 1;
            }
        };
        $user->id = $userId;
        $user->manage = $manage;
        $this->actingAs($user, 'api');
    }

    public function test_dispatch_assignments_patch_and_filters_preserve_composition_and_scope(): void
    {
        DB::table('users')->insert(['id' => 2, 'login' => 'worker', 'status' => '1']);
        DB::table('users_subdivisions')->insert([
            ['user_id' => 1, 'subdivision_id' => 1], ['user_id' => 2, 'subdivision_id' => 1],
        ]);
        $this->signInForOutlays();
        $data = $this->payload() + ['performer_ids' => [1, 2], 'return_performer_ids' => [2],
            'assembler_ids' => [1], 'acceptor_ids' => [2], 'self_pickup' => true, 'self_return' => false,
            'return_date_comment' => 'After event'];
        $response = $this->postJson('/api/admin/outlays/store', $data)->assertOk()
            ->assertJsonPath('data.item.performer_ids', [1, 2])->assertJsonPath('data.item.return_performer_ids', [2])
            ->assertJsonPath('data.item.self_pickup', true)->assertJsonPath('data.item.self_return', false);
        $id = $response->json('data.item.id');
        $originalItems = DB::table('outlay_items')->get()->toArray();
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['performer_ids' => []])->assertOk()
            ->assertJsonPath('data.item.performer_ids', [])->assertJsonPath('data.item.acceptor_ids', [2]);
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', [])->assertUnprocessable();
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['performer_ids' => [1, 1]])->assertUnprocessable();
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['performer_ids' => [999]])->assertUnprocessable();
        DB::table('users_subdivisions')->where('user_id', 2)->delete();
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['performer_ids' => [2]])->assertUnprocessable();
        DB::table('users_subdivisions')->insert(['user_id' => 2, 'subdivision_id' => 1]);
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['performer_ids' => [1, 2]])->assertOk();
        $this->assertEquals($originalItems, DB::table('outlay_items')->get()->toArray());

        $this->putJson('/api/admin/outlays/'.$id, $this->payload())->assertOk()
            ->assertJsonPath('data.item.performer_ids', [1, 2])->assertJsonPath('data.item.self_pickup', true);
        $originalItems = DB::table('outlay_items')->get()->toArray();
        $foreign = $this->save($this->payload());
        DB::table('outlays')->where('id', $foreign->id)->update(['subdivision_id' => 2]);
        $this->postJson('/api/admin/outlays')->assertOk()->assertJsonPath('data.items.total', 1);
        $this->postJson('/api/admin/outlays', ['self_return' => 'false', 'self_pickup' => true, 'performer_ids' => [1, 2], 'acceptor_ids' => [2], 'return_date_comment' => 'event'])
            ->assertOk()->assertJsonPath('data.items.total', 1)->assertJsonCount(2, 'data.items.data.0.performers');
        $this->postJson('/api/admin/outlays', ['self_pickup' => 0])->assertOk()->assertJsonPath('data.items.total', 0);
        $this->postJson('/api/admin/outlays', ['status_id' => 1, 'status_ids' => []])->assertUnprocessable();
        $this->postJson('/api/admin/outlays', ['sort' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/admin/outlays', ['assigned' => 'invalid', 'like' => 'invalid'])->assertOk()->assertJsonPath('data.items.total', 1);
        $this->postJson('/api/admin/outlays', ['subdivision_id' => 2])->assertForbidden();
        $this->getJson('/api/admin/outlays/'.$foreign->id)->assertForbidden();
        $this->patchJson('/api/admin/outlays/'.$foreign->id.'/assignments', ['performer_ids' => []])->assertForbidden();
        $this->patchJson('/api/admin/outlays/'.$foreign->id.'/status', ['status_id' => 1])->assertForbidden();
        $this->assertEquals($originalItems, DB::table('outlay_items')->where('outlay_group_id', DB::table('outlay_groups')->where('outlay_id', $id)->value('id'))->get()->toArray());
        $this->signInForOutlays(false);
        $this->patchJson('/api/admin/outlays/'.$id.'/assignments', ['acceptor_ids' => []])->assertForbidden();
    }

    public function test_return_range_is_independent_from_order_month_and_handles_null_and_boundaries(): void
    {
        $this->signInForOutlays();
        $a = $this->save(array_replace($this->payload(), ['return_date' => '2026-11-01 00:00:00']));
        $b = $this->save(array_replace($this->payload(), ['return_date' => '2026-11-30 23:59:59']));
        $this->save(array_replace($this->payload(), ['return_date' => null]));
        $this->save(array_replace($this->payload(), ['return_date' => '2026-12-01 00:00:00']));
        $this->postJson('/api/admin/outlays', ['return_date_from' => '2026-11-01', 'return_date_to' => '2026-11-30', 'sort' => 'return_date'])
            ->assertOk()->assertJsonPath('data.items.total', 2)->assertJsonPath('data.items.data.0.id', $a->id)->assertJsonPath('data.items.data.1.id', $b->id);
        $this->postJson('/api/admin/outlays', ['return_date_to' => '2026-11-01'])->assertOk()->assertJsonPath('data.items.total', 1);
        $this->postJson('/api/admin/outlays', ['return_date_from' => '2026-12-01'])->assertOk()->assertJsonPath('data.items.total', 1);
        $this->postJson('/api/admin/outlays', ['return_date_from' => '2026-12-01', 'return_date_to' => '2026-11-01'])->assertUnprocessable();
    }

    public function test_quick_status_syncs_stock_idempotently_without_rewriting_lines(): void
    {
        $this->signInForOutlays();
        DB::table('statuses')->insert(['id' => 2, 'affects_stock' => true]);
        $outlay = $this->save($this->payload());
        $items = DB::table('outlay_items')->get()->toArray();
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/status', ['status_id' => 2])->assertOk()->assertJsonPath('data.item.status_id', 2);
        $this->assertDatabaseCount('product_rentals', 2);
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/status', ['status_id' => 2])->assertOk();
        $this->assertDatabaseCount('product_rentals', 2);
        $this->assertEquals($items, DB::table('outlay_items')->get()->toArray());
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/status', ['status_id' => 1])->assertOk();
        $this->assertSame(0, DB::table('product_rentals')->whereIn('status', ['reserved', 'issued'])->count());
        $this->assertDatabaseCount('cash_box_transactions', 0);
        DB::table('product_stocks')->update(['quantity' => 0]);
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/status', ['status_id' => 2])->assertUnprocessable();
        $this->assertSame(1, (int) $outlay->fresh()->status_id);
        $this->assertSame(0, DB::table('product_rentals')->whereIn('status', ['reserved', 'issued'])->count());
    }

    public function test_dispatch_migration_can_rollback_without_dropping_existing_outlays(): void
    {
        $outlay = $this->save($this->payload());
        $this->assertFalse($outlay->self_pickup);
        $this->assertFalse($outlay->self_return);
        $this->assertSame([], $outlay->performer_ids);
        $migration = require app_path('Modules/Admin/Outlays/Migrations/2026_10_17_000001_add_outlay_dispatch_fields.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('outlay_assignments'));
        $this->assertFalse(Schema::hasColumn('outlays', 'self_pickup'));
        $this->assertDatabaseCount('outlays', 1);
        $this->assertDatabaseCount('outlay_items', 2);
        $migration->up();
        $this->assertFalse(Outlay::findOrFail($outlay->id)->self_pickup);
    }

    public function test_item_stages_are_independent_audited_and_aggregated_by_current_store(): void
    {
        $this->signInForOutlays();
        DB::table('stores')->insert([['id' => 1, 'title' => 'Main'], ['id' => 2, 'title' => 'Other']]);
        DB::table('products')->where('id', 1)->update(['store_id' => 1]);
        $outlay = $this->save($this->payload());
        $ids = $outlay->groups->first()->items->modelKeys();
        $url = '/api/admin/outlays/'.$outlay->id.'/items/';
        $this->getJson('/api/admin/outlays/'.$outlay->id)->assertOk()
            ->assertJsonPath('data.item.groups.0.items.0.stages.assembled', false);
        $beforeStock = DB::table('product_stocks')->get()->toArray();
        $response = $this->patchJson($url.$ids[0].'/stages', ['assembled' => true, 'defective' => true])->assertOk()
            ->assertJsonPath('data.item.stages.assembled', true)->assertJsonPath('data.item.stages.defective', true)
            ->assertJsonPath('data.item.stages.updated_by_id', 1)->assertJsonPath('data.item.stages.updated_by.id', 1);
        $timestamp = $response->json('data.item.stages.updated_at');
        $this->travel(1)->minutes();
        $this->patchJson($url.$ids[0].'/stages', ['assembled' => true])->assertOk()->assertJsonPath('data.item.stages.updated_at', $timestamp);
        $this->postJson('/api/admin/outlays')->assertOk()->assertJsonPath('data.items.data.0.stores.0.stages.total', 2)
            ->assertJsonPath('data.items.data.0.stores.0.stages.assembled', 1)->assertJsonPath('data.items.data.0.stores.0.stages.defective', 1);
        $this->patchJson($url.$ids[1].'/stages', ['assembled' => true, 'uploaded' => true, 'retrieved' => true, 'checked' => false, 'lost' => true])->assertOk();
        $this->postJson('/api/admin/outlays')->assertOk()->assertJsonPath('data.items.data.0.stores.0.stages.assembled', 2)
            ->assertJsonPath('data.items.data.0.stores.0.stages.uploaded', 1)->assertJsonPath('data.items.data.0.stores.0.stages.lost', 1);
        $this->patchJson($url.$ids[0].'/stages', ['assembled' => false])->assertOk()->assertJsonPath('data.item.stages.defective', true);
        $this->patchJson($url.$ids[1].'/stages', ['assembled' => false])->assertOk();
        DB::table('products')->where('id', 1)->update(['store_id' => 2]);
        $this->postJson('/api/admin/outlays')->assertOk()->assertJsonCount(1, 'data.items.data.0.stores')
            ->assertJsonPath('data.items.data.0.stores.0.id', 2)->assertJsonPath('data.items.data.0.stores.0.stages.assembled', 0);
        $this->assertEquals($beforeStock, DB::table('product_stocks')->get()->toArray());
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('product_rentals', 0);
        $this->assertDatabaseCount('outlay_item_stages', 2);
        $outlay->delete();
        $this->assertDatabaseCount('outlay_item_stages', 0);
    }

    public function test_item_stage_endpoint_checks_ownership_permissions_and_input(): void
    {
        $this->signInForOutlays();
        $first = $this->save($this->payload());
        $second = $this->save($this->payload());
        $itemId = $first->groups->first()->items->first()->id;
        $own = '/api/admin/outlays/'.$first->id.'/items/'.$itemId.'/stages';
        $foreign = '/api/admin/outlays/'.$second->id.'/items/'.$itemId.'/stages';
        $this->patchJson($foreign, ['assembled' => true])->assertForbidden();
        $this->patchJson($own, [])->assertUnprocessable();
        $this->patchJson($own, ['assembled' => null])->assertUnprocessable();
        $this->patchJson($own, ['lost' => 'invalid'])->assertUnprocessable();
        DB::table('outlays')->where('id', $first->id)->update(['subdivision_id' => 2]);
        $this->patchJson($own, ['assembled' => true])->assertForbidden();
        DB::table('outlays')->where('id', $first->id)->update(['subdivision_id' => 1]);
        $this->signInForOutlays(false);
        $this->patchJson($own, ['assembled' => true])->assertForbidden();
        $this->assertDatabaseCount('outlay_item_stages', 0);
    }

    public function test_item_comments_and_work_notes_are_partial_idempotent_private_and_do_not_touch_accounting(): void
    {
        foreach (glob(app_path('Modules/Admin/Clients/Migrations/*.php')) as $migration) {
            (require $migration)->up();
        }
        $this->signInForOutlays();
        $data = $this->payload();
        $data['is_paid'] = true;
        $data['date_of_paid'] = '2026-10-01';
        $data['payment_cash_box_id'] = 1;
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        $outlay = $this->save($data);
        $item = $outlay->groups->first()->items->first();
        $base = '/api/admin/outlays/'.$outlay->id;
        $comments = $base.'/items/'.$item->id.'/comments';
        $tables = ['outlays', 'outlay_groups', 'outlay_items', 'product_stocks', 'product_rentals', 'stock_movements', 'cash_boxes', 'cash_box_transactions'];
        // Operational updates must leave the composition and accounting byte-for-byte unchanged.
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->get()->toArray()])->all();
        $response = $this->patchJson($comments, ['assembly_comment' => 'Collect carefully', 'retrieve_comment' => 'Call first', 'updated_by_id' => 999])->assertOk()
            ->assertJsonPath('data.item.comments.assembly_comment', 'Collect carefully')
            ->assertJsonPath('data.item.comments.editors.assembly_comment.user.id', 1)
            ->assertJsonMissingPath('data.item.comments.editors.assembly_comment.user.password');
        $assemblyTime = $response->json('data.item.comments.editors.assembly_comment.updated_at');
        $retrieveTime = $response->json('data.item.comments.editors.retrieve_comment.updated_at');
        $this->travel(1)->minutes();
        $this->patchJson($comments, ['assembly_comment' => 'Collect carefully'])->assertOk()
            ->assertJsonPath('data.item.comments.editors.assembly_comment.updated_at', $assemblyTime)
            ->assertJsonPath('data.item.comments.retrieve_comment', 'Call first');
        $this->patchJson($comments, ['assembly_comment' => ''])->assertOk()
            ->assertJsonPath('data.item.comments.assembly_comment', null)
            ->assertJsonPath('data.item.comments.editors.retrieve_comment.updated_at', $retrieveTime);
        $this->patchJson($comments, ['retrieve_comment' => null])->assertOk()
            ->assertJsonPath('data.item.comments.retrieve_comment', null);

        $response = $this->patchJson($base.'/work-notes', ['additional_upload_comment' => 'Extra chairs', 'retriever_comment' => 'Evening'])->assertOk()
            ->assertJsonPath('data.item.work_notes.editors.additional_upload_comment.user.id', 1);
        $noteTime = $response->json('data.item.work_notes.editors.additional_upload_comment.updated_at');
        $this->travel(1)->minutes();
        $this->patchJson($base.'/work-notes', ['additional_upload_comment' => 'Extra chairs'])->assertOk()
            ->assertJsonPath('data.item.work_notes.editors.additional_upload_comment.updated_at', $noteTime);
        $this->patchJson($base.'/work-notes', ['damage_loss_comment' => 'One scratched', 'retriever_comment' => null])->assertOk()
            ->assertJsonPath('data.item.work_notes.additional_upload_comment', 'Extra chairs')
            ->assertJsonPath('data.item.work_notes.retriever_comment', null);
        $this->patchJson($base.'/items/'.$item->id.'/stages', ['lost' => true, 'defective' => true])->assertOk();
        foreach ($before as $table => $rows) {
            $this->assertEquals($rows, DB::table($table)->get()->toArray(), $table.' changed');
        }
        $this->getJson($base.'/info')->assertOk()
            ->assertJsonPath('data.item.work_notes.damage_loss_comment', 'One scratched')
            ->assertJsonPath('data.item.groups.0.items.0.comments.assembly_comment', null);
        $this->getJson('/api/pub/outlays/'.$outlay->public_uuid)->assertOk()
            ->assertJsonMissingPath('data.item.work_notes')
            ->assertJsonMissingPath('data.item.groups.0.items.0.comments')
            ->assertJsonMissingPath('data.item.groups.0.items.0.stages');
        $this->patchJson($comments, ['assembly_comment' => 'Keep on edit'])->assertOk();
        $data['groups'][0]['id'] = $outlay->groups->first()->id;
        foreach ($outlay->groups->first()->items as $index => $savedItem) {
            $data['groups'][0]['items'][$index]['id'] = $savedItem->id;
        }
        $this->save($data, $outlay);
        $this->assertDatabaseHas('outlay_work_notes', ['outlay_id' => $outlay->id, 'damage_loss_comment' => 'One scratched']);
        $this->assertDatabaseHas('outlay_item_comments', ['outlay_item_id' => $item->id, 'assembly_comment' => 'Keep on edit']);
        $outlay->delete();
        $this->assertDatabaseCount('outlay_work_notes', 0);
        $this->assertDatabaseCount('outlay_item_comments', 0);
    }

    public function test_operational_comments_require_permission_scope_ownership_and_valid_input(): void
    {
        $first = $this->save($this->payload());
        $second = $this->save($this->payload());
        $item = $first->groups->first()->items->first();
        $comments = '/api/admin/outlays/'.$first->id.'/items/'.$item->id.'/comments';
        $notes = '/api/admin/outlays/'.$first->id.'/work-notes';
        $this->patchJson($comments, ['assembly_comment' => 'Test'])->assertUnauthorized();
        $this->patchJson($notes, ['damage_loss_comment' => 'Test'])->assertUnauthorized();
        $this->signInForOutlays(false);
        $this->patchJson($comments, ['assembly_comment' => 'Test'])->assertForbidden();
        $this->patchJson($notes, ['damage_loss_comment' => 'Test'])->assertForbidden();
        $this->signInForOutlays();
        $this->patchJson('/api/admin/outlays/'.$second->id.'/items/'.$item->id.'/comments', ['assembly_comment' => 'Test'])->assertForbidden();
        $this->patchJson($comments, [])->assertUnprocessable();
        $this->patchJson($notes, [])->assertUnprocessable();
        $this->patchJson($comments, ['assembly_comment' => ['invalid']])->assertUnprocessable();
        $this->patchJson($comments, ['retrieve_comment' => str_repeat('a', 10001)])->assertUnprocessable();
        $this->patchJson($notes, ['retriever_comment' => str_repeat('a', 10001)])->assertUnprocessable();
        DB::table('outlays')->where('id', $first->id)->update(['subdivision_id' => 2]);
        $this->patchJson($comments, ['assembly_comment' => 'Test'])->assertForbidden();
        $this->patchJson($notes, ['damage_loss_comment' => 'Test'])->assertForbidden();
        $this->assertDatabaseCount('outlay_item_comments', 0);
        $this->assertDatabaseCount('outlay_work_notes', 0);
    }

    public function test_stage_editors_are_independent_and_record_unsetting_but_not_noops(): void
    {
        DB::table('users')->insert(['id' => 2, 'login' => 'second']);
        $outlay = $this->save($this->payload());
        $item = $outlay->groups->first()->items->first();
        $url = '/api/admin/outlays/'.$outlay->id.'/items/'.$item->id.'/stages';
        $this->signInForOutlays();
        $this->patchJson($url, ['checked' => false])->assertOk()
            ->assertJsonPath('data.item.stages.editors.checked.user', null)
            ->assertJsonPath('data.item.stages.editors.checked.updated_at', null);
        $this->assertDatabaseCount('outlay_item_stages', 0);
        $response = $this->patchJson($url, ['assembled' => true])->assertOk()
            ->assertJsonPath('data.item.stages.editors.assembled.user.id', 1);
        $time = $response->json('data.item.stages.editors.assembled.updated_at');
        $this->travel(1)->minutes();
        $this->signInForOutlays(true, 2);
        $this->patchJson($url, ['lost' => true, 'assembled' => true, 'assembled_updated_by_id' => 2])->assertOk()
            ->assertJsonPath('data.item.stages.editors.assembled.user.id', 1)
            ->assertJsonPath('data.item.stages.editors.assembled.updated_at', $time)
            ->assertJsonPath('data.item.stages.editors.lost.user.id', 2)
            ->assertJsonPath('data.item.stages.updated_by.id', 2);
        $this->patchJson($url, ['assembled' => false])->assertOk()
            ->assertJsonPath('data.item.stages.editors.assembled.user.id', 2);
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/items/'.$item->id.'/comments', ['assembly_comment' => 'A', 'retrieve_comment' => 'B'])->assertOk();
        $this->signInForOutlays();
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/items/'.$item->id.'/comments', ['retrieve_comment' => null])->assertOk()
            ->assertJsonPath('data.item.comments.editors.assembly_comment.user.id', 2)
            ->assertJsonPath('data.item.comments.editors.retrieve_comment.user.id', 1);
    }

    public function test_operational_migration_does_not_invent_legacy_stage_editors_and_can_roll_back(): void
    {
        $outlay = $this->save($this->payload());
        $item = $outlay->groups->first()->items->first();
        $migration = require app_path('Modules/Admin/OutlayGroups/Migrations/2026_10_20_000001_add_outlay_work_notes_and_item_comments.php');
        $migration->down();
        DB::table('outlay_item_stages')->insert(['outlay_item_id' => $item->id, 'assembled' => true, 'updated_by_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $stage = $item->stages()->first();
        $this->assertTrue($stage->assembled);
        $this->assertSame(1, $stage->updated_by_id);
        $this->assertNull($stage->editors['assembled']['user']);
        $this->assertNull($stage->editors['assembled']['updated_at']);
        $migration->down();
        $this->assertDatabaseHas('outlay_item_stages', ['outlay_item_id' => $item->id, 'assembled' => true]);
        $this->assertFalse(Schema::hasTable('outlay_item_comments'));
        $this->assertFalse(Schema::hasTable('outlay_work_notes'));
    }

    public function test_operational_migration_can_resume_after_partial_mysql_ddl(): void
    {
        $outlay = $this->save($this->payload());
        Schema::drop('outlay_work_notes');
        Schema::create('outlay_work_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('outlay_id')->primary();
            foreach (['additional_upload_comment', 'retriever_comment', 'damage_loss_comment'] as $field) {
                $table->text($field)->nullable();
                $table->unsignedBigInteger($field.'_updated_by_id')->nullable();
                $table->timestamp($field.'_updated_at')->nullable();
            }
            $table->timestamps();
        });
        DB::table('outlay_work_notes')->insert(['outlay_id' => $outlay->id, 'retriever_comment' => 'Keep existing data']);
        $migration = require app_path('Modules/Admin/OutlayGroups/Migrations/2026_10_20_000001_add_outlay_work_notes_and_item_comments.php');
        $migration->up();
        $migration->up();
        $this->assertDatabaseHas('outlay_work_notes', ['outlay_id' => $outlay->id, 'retriever_comment' => 'Keep existing data']);
        $this->assertCount(4, Schema::getForeignKeys('outlay_work_notes'));
        $this->assertCount(8, Schema::getForeignKeys('outlay_item_stages'));
        $outlay->delete();
        $this->assertDatabaseCount('outlay_work_notes', 0);
    }

    public function test_audit_tracks_real_author_form_only_changes_and_all_creator_changes(): void
    {
        DB::table('users')->where('id', 1)->update(['login' => 'admin']);
        DB::table('users')->insert([['id' => 2, 'login' => 'worker'], ['id' => 3, 'login' => 'third']]);
        $this->signInForOutlays();
        $data = $this->payload();
        $data['creator_id'] = 2;
        $id = $this->postJson('/api/admin/outlays/store', $data)->assertOk()->json('data.item.id');
        $outlay = Outlay::with('groups.items')->findOrFail($id);
        $base = '/api/admin/outlays/'.$id;
        $this->getJson($base.'/audit')->assertOk()
            ->assertJsonPath('data.summary.created.user.login', 'admin')
            ->assertJsonPath('data.summary.last_edited', null)
            ->assertJsonPath('data.summary.creator_changed', null)
            ->assertJsonPath('data.items.total', 2);
        $data['groups'][0]['id'] = $outlay->groups->first()->id;
        foreach ($outlay->groups->first()->items as $index => $item) {
            $data['groups'][0]['items'][$index]['id'] = $item->id;
        }
        $this->putJson($base, $data)->assertOk();
        $this->assertDatabaseCount('outlay_audit_events', 2);
        $this->travel(1)->minutes();
        $this->signInForOutlays(true, 2);
        $data['groups'][0]['items'][0]['description'] = 'Changed only this line';
        $this->putJson($base, $data)->assertOk();
        $response = $this->getJson($base.'/audit')->assertOk()
            ->assertJsonPath('data.summary.last_edited.user.login', 'worker')
            ->assertJsonPath('data.summary.created.user.login', 'admin');
        $lastEdit = $response->json('data.summary.last_edited');
        $this->travel(1)->minutes();
        $this->putJson($base, $data)->assertOk();
        $this->assertDatabaseCount('outlay_audit_events', 3);
        $this->getJson($base.'/audit')->assertOk()->assertJsonPath('data.summary.last_edited', $lastEdit);
        $data['creator_id'] = 3;
        $this->putJson($base, $data)->assertOk();
        $this->getJson($base.'/audit?event=creator_changed')->assertOk()
            ->assertJsonPath('data.items.total', 1)
            ->assertJsonPath('data.items.data.0.from_creator.id', 2)
            ->assertJsonPath('data.items.data.0.to_creator.id', 3)
            ->assertJsonPath('data.items.data.0.user.login', 'worker');
        $this->signInForOutlays();
        $data['creator_id'] = 2;
        $this->putJson($base, $data)->assertOk();
        $data['creator_id'] = 3;
        $this->putJson($base, $data)->assertOk();
        DB::table('users')->where('id', 2)->delete();
        $this->getJson($base.'/audit?event=creator_changed')->assertOk()
            ->assertJsonPath('data.items.total', 3)
            ->assertJsonPath('data.items.data.2.user.login', 'worker')
            ->assertJsonPath('data.items.data.2.from_creator.login', 'worker');
    }

    public function test_audit_status_history_has_snapshots_stable_pagination_and_no_operational_edits(): void
    {
        DB::table('statuses')->insert(['id' => 2, 'title' => 'Assembled']);
        $this->signInForOutlays();
        $outlay = $this->save($this->payload());
        $data = $this->payload();
        $data['groups'][0]['id'] = $outlay->groups->first()->id;
        foreach ($outlay->groups->first()->items as $index => $item) {
            $data['groups'][0]['items'][$index]['id'] = $item->id;
        }
        $base = '/api/admin/outlays/'.$outlay->id;
        $data['status_id'] = 2;
        $this->putJson($base, $data)->assertOk();
        $lastEdit = $this->getJson($base.'/audit')->assertOk()->json('data.summary.last_edited');
        $this->travel(1)->minutes();
        $this->patchJson($base.'/status', ['status_id' => 1])->assertOk();
        $this->patchJson($base.'/status', ['status_id' => 1])->assertOk();
        DB::table('statuses')->where('id', 2)->update(['title' => 'Renamed']);
        $page = $this->getJson($base.'/audit?event=status_changed&page_size=1')->assertOk()
            ->assertJsonPath('data.items.total', 3)->assertJsonPath('data.items.last_page', 3)
            ->assertJsonPath('data.items.data.0.from_status.title', 'Assembled')
            ->assertJsonPath('data.items.data.0.to_status.title', 'New');
        $second = $this->getJson($base.'/audit?event=status_changed&page_size=1&page=2')->assertOk()
            ->assertJsonPath('data.items.data.0.to_status.title', 'Assembled');
        $this->assertGreaterThan($second->json('data.items.data.0.id'), $page->json('data.items.data.0.id'));
        $this->getJson($base.'/audit?event=status_changed&page_size=1&page=3')->assertOk()
            ->assertJsonPath('data.items.data.0.from_status', null);
        $count = DB::table('outlay_audit_events')->count();
        $itemId = $outlay->groups->first()->items->first()->id;
        $this->patchJson($base.'/items/'.$itemId.'/stages', ['assembled' => true])->assertOk();
        $this->patchJson($base.'/items/'.$itemId.'/comments', ['assembly_comment' => 'Comment'])->assertOk();
        $this->patchJson($base.'/work-notes', ['retriever_comment' => 'Note'])->assertOk();
        $this->postJson($base.'/public-link/rotate')->assertOk();
        $this->getJson($base.'/audit')->assertOk()->assertJsonPath('data.summary.last_edited', $lastEdit);
        $this->assertDatabaseCount('outlay_audit_events', $count);
        $this->getJson('/api/pub/outlays/'.$outlay->fresh()->public_uuid)->assertOk()
            ->assertJsonMissingPath('data.item.audit')->assertJsonMissingPath('data.summary');
    }

    public function test_audit_checks_read_permission_scope_and_does_not_invent_old_authors(): void
    {
        $outlay = $this->save($this->payload());
        $url = '/api/admin/outlays/'.$outlay->id.'/audit';
        $this->getJson($url)->assertUnauthorized();
        $this->signInForOutlays(false);
        $this->getJson($url)->assertOk();
        $this->getJson($url.'?page=0')->assertUnprocessable();
        $this->getJson($url.'?page_size=101')->assertUnprocessable();
        $this->getJson($url.'?event=invalid')->assertUnprocessable();
        DB::table('outlays')->where('id', $outlay->id)->update(['subdivision_id' => 2]);
        $this->getJson($url)->assertForbidden();
        DB::table('outlays')->where('id', $outlay->id)->update(['subdivision_id' => 1]);
        DB::table('outlay_audit_events')->where('outlay_id', $outlay->id)->delete();
        $this->getJson($url)->assertOk()->assertJsonPath('data.summary.created.user', null)
            ->assertJsonPath('data.summary.created.at', $outlay->created_at->format('Y-m-d H:i:s'))
            ->assertJsonPath('data.summary.created.source', 'unknown')
            ->assertJsonPath('data.summary.last_edited', null)
            ->assertJsonPath('data.items.total', 0);
        $noRead = new class extends User
        {
            public function canDo($alias, $require = false)
            {
                return false;
            }
        };
        $noRead->id = 1;
        $this->actingAs($noRead, 'api');
        $this->getJson($url)->assertForbidden();
    }

    public function test_audit_rolls_back_with_outlay_and_copy_has_its_own_history(): void
    {
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        $original = $this->save($this->payload());
        $count = DB::table('outlay_audit_events')->count();
        $bad = $this->payload();
        $bad['groups'][0]['items'][0]['quantity'] = 100;
        try {
            $this->save($bad);
            $this->fail('Insufficient stock was accepted.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('outlay_audit_events', $count);
            $this->assertDatabaseCount('outlays', 1);
        }
        try {
            DB::transaction(function () {
                $this->save($this->payload());
                throw new \RuntimeException('Rollback after recording audit');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Rollback after recording audit', $exception->getMessage());
        }
        $this->assertDatabaseCount('outlay_audit_events', $count);
        $copy = $this->save($this->payload(), $original->fresh()->replicate());
        $this->assertNotSame($original->id, $copy->id);
        $this->assertNotSame($original->public_uuid, $copy->public_uuid);
        $this->assertSame(2, DB::table('outlay_audit_events')->where('outlay_id', $copy->id)->count());
        $this->assertSame(1, DB::table('outlay_audit_events')->where('outlay_id', $copy->id)->where('event', 'created')->count());
        DB::transaction(fn () => app(\App\Modules\Admin\Outlays\Services\OutlayAuditService::class)->statusChanged($copy, 1, 2, null));
        $this->assertDatabaseHas('outlay_audit_events', ['outlay_id' => $copy->id, 'source' => 'system', 'user_snapshot' => null]);
    }

    public function test_failed_status_patch_leaves_status_and_audit_unchanged(): void
    {
        DB::table('statuses')->insert(['id' => 2, 'title' => 'Reserve', 'affects_stock' => true]);
        $data = $this->payload();
        $data['groups'][0]['items'][0]['quantity'] = 100;
        $data['is_rent'] = true;
        $outlay = $this->save($data);
        $this->signInForOutlays();
        $this->patchJson('/api/admin/outlays/'.$outlay->id.'/status', ['status_id' => 2])->assertUnprocessable();
        $this->assertDatabaseHas('outlays', ['id' => $outlay->id, 'status_id' => 1]);
        $this->assertDatabaseCount('outlay_audit_events', 2);
    }

    public function test_public_uuid_is_generated_private_fields_are_hidden_and_link_can_be_rotated(): void
    {
        DB::table('subdivisions')->where('id', 1)->update(['description' => 'Subdivision description']);
        foreach (glob(app_path('Modules/Admin/Clients/Migrations/*.php')) as $migration) {
            (require $migration)->up();
        }
        $outlay = $this->save($this->payload());
        $second = $this->save($this->payload());
        $this->assertTrue(\Illuminate\Support\Str::isUuid($outlay->public_uuid));
        $this->assertNotSame($outlay->public_uuid, $second->public_uuid);
        $url = '/api/pub/outlays/'.$outlay->public_uuid;
        $this->getJson($url)->assertOk()->assertJsonPath('data.item.title', 'Event')
            ->assertJsonPath('data.item.subdivision.description', 'Subdivision description')
            ->assertJsonPath('data.item.groups.0.items.0.product_title_snapshot', 'Chair')
            ->assertJsonMissingPath('data.item.creator_id')->assertJsonMissingPath('data.item.client_id')
            ->assertJsonMissingPath('data.item.performers')->assertJsonMissingPath('data.item.payment_cash_box_id')
            ->assertJsonMissingPath('data.item.groups.0.items.0.stages')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->getJson('/api/pub/outlays/'.$outlay->id)->assertNotFound();
        $this->getJson('/api/pub/outlays/'.\Illuminate\Support\Str::uuid())->assertNotFound();
        $this->getJson('/api/admin/outlays/'.$outlay->id.'/info')->assertUnauthorized();
        $this->signInForOutlays(false);
        $this->getJson('/api/admin/outlays/'.$outlay->id.'/documents')->assertOk()->assertJsonCount(8, 'data.items');
        $this->getJson('/api/admin/outlays/'.$outlay->id.'/info')->assertOk()
            ->assertJsonPath('data.public_api_url', route('api.pub.outlays.show', ['uuid' => $outlay->public_uuid]));
        $this->postJson('/api/admin/outlays/'.$outlay->id.'/public-link/rotate')->assertForbidden();
        $this->signInForOutlays();
        $newUuid = $this->postJson('/api/admin/outlays/'.$outlay->id.'/public-link/rotate')->assertOk()->json('data.public_uuid');
        $this->assertNotSame($outlay->public_uuid, $newUuid);
        $this->getJson($url)->assertNotFound();
        $this->getJson('/api/pub/outlays/'.$newUuid)->assertOk();
        $this->putJson('/api/admin/outlays/'.$outlay->id, $this->payload() + ['public_uuid' => (string) \Illuminate\Support\Str::uuid()])
            ->assertOk()->assertJsonPath('data.item.public_uuid', $newUuid);
        DB::table('outlays')->where('id', $second->id)->update(['subdivision_id' => 2]);
        $this->getJson('/api/admin/outlays/'.$second->id.'/info')->assertForbidden();
        $this->getJson('/api/admin/outlays/'.$second->id.'/documents/estimate')->assertForbidden();
        $this->getJson('/api/admin/outlays/'.$outlay->id.'/documents/unknown')->assertNotFound();
    }

    public function test_uuid_migration_backfills_existing_outlays_without_changing_business_data(): void
    {
        $outlay = $this->save($this->payload());
        $second = $this->save($this->payload());
        $migration = require app_path('Modules/Admin/Outlays/Migrations/2026_10_19_000001_add_public_uuid_to_outlays.php');
        $before = DB::table('outlays')->where('id', $outlay->id)->value('updated_at');
        $migration->down();
        $migration->up();
        $tokens = DB::table('outlays')->pluck('public_uuid')->all();
        $this->assertCount(2, array_unique($tokens));
        foreach ($tokens as $token) {
            $this->assertTrue(\Illuminate\Support\Str::isUuid($token));
        }
        $this->assertSame($before, DB::table('outlays')->where('id', $outlay->id)->value('updated_at'));
        $this->assertDatabaseCount('outlay_items', 4);
    }

    public function test_documents_generate_from_legacy_templates_and_saved_prices(): void
    {
        if (! class_exists(\ZipArchive::class) || ! extension_loaded('gd')) {
            $this->markTestSkipped('Office export requires zip and gd.');
        }
        foreach (glob(app_path('Modules/Admin/Clients/Migrations/*.php')) as $migration) {
            (require $migration)->up();
        }
        DB::table('individuals')->insert([
            'id' => 1, 'name' => 'Test & Client <Example>', 'phone' => '70000000000', 'client_type_id' => 1,
            'birthday' => '1990-01-02', 'passport_serial' => 1234, 'passport_number' => 123456,
            'date_of_passport' => '2010-02-03', 'issued_by' => 'Test office', 'registration' => 'Test address',
        ]);
        $outlay = $this->save($this->payload() + ['percent_discount' => 10]);
        DB::table('products')->where('id', 1)->update(['title' => 'Changed catalog title']);
        $service = app(\App\Modules\Admin\Outlays\Services\OutlayDocumentService::class);
        $qa = storage_path('app/private/outlay-document-qa');
        if (getenv('OUTLAY_DOCUMENT_QA')) {
            \Illuminate\Support\Facades\File::ensureDirectoryExists($qa);
        }
        foreach (['contract', 'lease-contract', 'estimate', 'service-act', 'specification-1', 'specification-2', 'estimate-pdf'] as $type) {
            $path = $service->generate($outlay, $type);
            try {
                $this->assertFileExists($path);
                $this->assertGreaterThan(500, filesize($path));
                if (str_ends_with($path, '.docx')) {
                    $zip = new \ZipArchive;
                    $zip->open($path);
                    $xml = $zip->getFromName('word/document.xml');
                    $doc = new \DOMDocument;
                    $this->assertTrue($doc->loadXML($xml));
                    $this->assertStringNotContainsString('${', $doc->textContent);
                    $this->assertStringContainsString('Test & Client <Example>', $doc->textContent);
                    $zip->close();
                } elseif (str_ends_with($path, '.xlsx')) {
                    $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
                    $sheet = $book->getActiveSheet();
                    $row = $type === 'service-act' ? 12 : 13;
                    $this->assertSame('Hall / Chair', $sheet->getCell('D'.$row)->getValue());
                    $this->assertEquals(13.5, $sheet->getCell(($type === 'specification-1' || $type === 'specification-2' ? 'AD' : 'Z').$row)->getValue());
                    $book->disconnectWorksheets();
                } else {
                    $this->assertStringStartsWith('%PDF-', file_get_contents($path));
                }
                if (getenv('OUTLAY_DOCUMENT_QA')) {
                    copy($path, $qa.'/'.$type.'.'.pathinfo($path, PATHINFO_EXTENSION));
                }
            } finally {
                @unlink($path);
            }
        }
        DB::table('legal_entities')->insert([
            'id' => 1, 'contact_name' => 'Test contact', 'organization_name' => 'Test company', 'client_type_id' => 1,
            'tin' => 1234567890, 'ogrn' => '1234567890123', 'legal_address' => 'Test office', 'bank_name' => 'Test bank',
            'bik' => 123456789, 'cpr' => 123456789, 'k_s' => 1234567890, 'r_s' => 1234567890, 'phone' => '70000000000',
        ]);
        $outlay->partner_entity = 'legal_entity';
        foreach (['return-act', 'lease-contract'] as $type) {
            $path = $service->generate($outlay, $type);
            try {
                $this->assertFileExists($path);
            } finally {
                @unlink($path);
            }
        }
    }

    public function test_document_download_checks_scope_and_missing_counterparty_data(): void
    {
        $outlay = $this->save($this->payload());
        $this->signInForOutlays();
        $this->getJson('/api/admin/outlays/'.$outlay->id.'/documents')->assertOk();
        foreach (glob(app_path('Modules/Admin/Clients/Migrations/*.php')) as $migration) {
            (require $migration)->up();
        }
        DB::table('individuals')->insert(['id' => 1, 'name' => 'Incomplete client', 'phone' => '70000000000', 'client_type_id' => 1]);
        if (class_exists(\ZipArchive::class)) {
            $this->getJson('/api/admin/outlays/'.$outlay->id.'/documents/contract')->assertUnprocessable()->assertJsonStructure(['errors' => ['client.birthday']]);
        }
        if (extension_loaded('gd')) {
            $response = $this->get('/api/admin/outlays/'.$outlay->id.'/documents/estimate-pdf')->assertOk()->assertDownload('outlay-'.$outlay->id.'-estimate-pdf.pdf');
            @unlink($response->baseResponse->getFile()->getPathname());
            $response = $this->get('/api/admin/outlays/documents/period-pdf?date_from=2026-10-01&date_to=2026-10-31')->assertOk();
            @unlink($response->baseResponse->getFile()->getPathname());
            $this->getJson('/api/admin/outlays/documents/period-pdf?date_from=2026-10-01&date_to=2026-10-31&subdivision_id=2')->assertForbidden();
        }
    }

    private function save(array $data, ?Outlay $outlay = null): Outlay
    {
        $request = OutlayRequest::create('/', 'POST', $data);
        $request->setUserResolver(fn () => new class
        {
            public int $id = 1;

            public function hasSubdivisionAccess($id): bool
            {
                return (int) $id === 1;
            }
        });
        $validator = Validator::make($data, $request->rules());
        $validator->validate();
        $request->setValidator($validator);

        return app(OutlayService::class)->save($request, $outlay ?? new Outlay);
    }

    private function deleteOutlay(Outlay $outlay): Outlay
    {
        $request = DeleteOutlayRequest::create('/', 'DELETE', ['subdivision_id' => 1]);
        $request->setUserResolver(fn () => new class
        {
            public int $id = 1;
        });

        return app(OutlayService::class)->delete($request, $outlay);
    }

    public function test_outlay_list_includes_unique_current_stores_in_one_batch_query(): void
    {
        DB::table('stores')->insert([
            ['id' => 1, 'title' => 'Main'], ['id' => 2, 'title' => 'Annex'],
            ['id' => 3, 'title' => 'Unused'],
        ]);
        DB::table('products')->where('id', 1)->update(['store_id' => 1]);
        DB::table('products')->insert([
            ['id' => 2, 'title' => 'Table', 'is_rent' => false, 'store_id' => 2],
            ['id' => 3, 'title' => 'Service', 'is_rent' => false, 'store_id' => null],
            ['id' => 4, 'title' => 'Other chair', 'is_rent' => true, 'store_id' => 1],
        ]);
        foreach ([2, 3, 4] as $productId) {
            DB::table('category_product')->insert(['category_id' => 1, 'product_id' => $productId]);
        }
        $data = $this->payload();
        $data['groups'][] = ['outlay_group_name_id' => 1, 'items' => [
            ['product_id' => 1, 'price' => 1, 'quantity' => 1],
            ['product_id' => 2, 'price' => 1, 'quantity' => 1],
            ['product_id' => 3, 'price' => 1, 'quantity' => 1],
            ['product_id' => 4, 'price' => 1, 'quantity' => 1],
        ]];
        $first = $this->save($data);
        $second = $this->save($this->payload());
        $empty = $this->save(array_replace($this->payload(), ['groups' => []]));
        $noStore = $this->payload();
        $noStore['groups'][0]['items'] = [['product_id' => 3, 'price' => 1, 'quantity' => 1]];
        $withoutStore = $this->save($noStore);

        $request = ListOutlaysRequest::create('/', 'GET', ['sort' => 'id', 'per_page' => 100]);
        $request->setUserResolver(fn () => new class
        {
            public function getAvailableSubdivisionIds(): array
            {
                return [1];
            }
        });
        DB::enableQueryLog();
        DB::flushQueryLog();
        $page = app(OutlayService::class)->getOutlays($request, new \App\Core\Filters\OutlaysFilter($request));
        $this->assertCount(9, DB::getQueryLog());
        DB::disableQueryLog();
        $items = collect($page->toArray()['data'])->keyBy('id');
        $stageCounts = fn ($total) => ['total' => $total, 'assembled' => 0, 'uploaded' => 0, 'retrieved' => 0, 'checked' => 0, 'lost' => 0, 'defective' => 0];
        $this->assertSame([['id' => 2, 'title' => 'Annex', 'stages' => $stageCounts(1)], ['id' => 1, 'title' => 'Main', 'stages' => $stageCounts(4)]], $items[$first->id]['stores']);
        $this->assertSame([['id' => 1, 'title' => 'Main', 'stages' => $stageCounts(2)]], $items[$second->id]['stores']);
        $this->assertSame([], $items[$empty->id]['stores']);
        $this->assertSame([], $items[$withoutStore->id]['stores']);
        $this->assertArrayNotHasKey('groups', $items[$first->id]);

        $user = new class extends User
        {
            public function getAvailableSubdivisionIds(): array
            {
                return [1];
            }

            public function canDo($alias, $require = false)
            {
                return in_array('OUTLAYS_ACCESS', (array) $alias, true);
            }
        };
        $user->id = 1;
        $this->actingAs($user, 'api');
        $this->postJson('/api/admin/outlays?page=2', ['sort' => 'id', 'per_page' => 1])
            ->assertOk()->assertJsonPath('data.items.total', 4)
            ->assertJsonPath('data.items.data.0.id', $second->id)
            ->assertJsonPath('data.items.data.0.stores', [['id' => 1, 'title' => 'Main', 'stages' => $stageCounts(2)]]);

        DB::table('products')->where('id', 1)->update(['store_id' => 2]);
        $this->postJson('/api/admin/outlays?page=2', ['sort' => 'id', 'per_page' => 1])
            ->assertOk()->assertJsonPath('data.items.data.0.stores', [['id' => 2, 'title' => 'Annex', 'stages' => $stageCounts(2)]]);
        $this->postJson('/api/admin/outlays?page=100')->assertOk()->assertJsonPath('data.items.data', []);
    }

    public function test_create_and_update_composition_with_stable_ids_and_images(): void
    {
        $data = $this->payload();
        $data['percent_discount'] = '10';
        $outlay = $this->save($data);
        $this->assertSame('16.20', $outlay->total_cost);
        $group = $outlay->groups->first();
        $item = $group->items->first();
        $this->assertSame('1.000', $item->quantity);
        $this->assertSame('/chair.jpg', $item->images->first()->path);
        $this->assertSame('Hall', $group->groupName->name);
        $data['groups'][0]['id'] = $group->id;
        $data['groups'][0]['items'] = [
            ['id' => $item->id, 'product_id' => 1, 'price' => '20.00', 'quantity' => '2', 'images' => []],
        ];
        $updated = $this->save($data, $outlay);
        $this->assertSame('36.00', $updated->total_cost);
        $this->assertSame($item->id, $updated->groups->first()->items->first()->id);
        $this->assertDatabaseCount('outlay_items', 1);
        $this->assertDatabaseCount('outlay_item_images', 0);
        $data['groups'] = [];
        $this->assertSame('0.00', $this->save($data, $updated)->total_cost);
        $this->assertDatabaseCount('outlay_groups', 0);
        $this->assertDatabaseCount('outlay_items', 0);
    }

    public function test_fixed_discount_rounding_matches_line_totals(): void
    {
        $data = $this->payload();
        $data['groups'][0]['items'] = array_fill(0, 3, ['product_id' => 1, 'price' => '0.01', 'quantity' => '1']);
        $data['discount_amount'] = '0.01';
        $data['percent_discount'] = '90';
        $outlay = $this->save($data);
        $this->assertSame('0.02', $outlay->total_cost);
        $this->assertEqualsWithDelta(0.02, $outlay->groups->first()->items->sum('total_cost'), 0.00001);
    }

    public function test_foreign_group_id_rolls_back_header_and_composition(): void
    {
        $first = $this->save($this->payload());
        $second = $this->save($this->payload());
        $data = $this->payload();
        $data['title'] = 'Must roll back';
        $data['groups'][0]['id'] = $first->groups->first()->id;
        try {
            $this->save($data, $second);
            $this->fail('Foreign group was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('groups.0.id', $e->errors());
        }
        $this->assertSame('Event', $second->fresh()->title);
        $this->assertDatabaseCount('outlay_items', 4);
    }

    public function test_product_from_another_subdivision_is_rejected(): void
    {
        DB::table('categories')->update(['subdivision_id' => 2]);
        try {
            $this->save($this->payload());
            $this->fail('Foreign product was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('groups.0.items.0.product_id', $e->errors());
        }
        $this->assertDatabaseCount('outlays', 0);
    }

    public function test_invalid_dates_quantity_and_duplicate_ids_are_rejected(): void
    {
        $data = $this->payload();
        $data['return_date'] = '2026-09-01';
        $data['groups'][0]['items'][0]['quantity'] = 0;
        $data['groups'][0]['items'][0]['id'] = 3;
        $data['groups'][0]['items'][1]['id'] = 3;
        $request = OutlayRequest::create('/', 'POST', $data);
        $errors = Validator::make($data, $request->rules())->errors();
        $this->assertTrue($errors->has('return_date'));
        $this->assertTrue($errors->has('groups.0.items.0.quantity'));
        $this->assertTrue($errors->has('groups.0.items.0.id'));
    }

    public function test_outlay_request_quantity_requires_positive_integer(): void
    {
        $rules = (new OutlayRequest)->rules();
        $quantityRules = ['groups.*.items.*.quantity' => $rules['groups.*.items.*.quantity']];

        foreach ([1.5, '1.500', 0, -1, 100000] as $quantity) {
            $validator = Validator::make(['groups' => [['items' => [['quantity' => $quantity]]]]], $quantityRules);
            $this->assertTrue($validator->errors()->has('groups.0.items.0.quantity'));
        }

        foreach ([1, '2', 99999] as $quantity) {
            $validator = Validator::make(['groups' => [['items' => [['quantity' => $quantity]]]]], $quantityRules);
            $this->assertTrue($validator->passes());
        }
    }

    public function test_outlay_images_include_public_url_without_changing_stored_path(): void
    {
        config(['filesystems.disks.images.url' => 'http://localhost/storage/images']);
        $path = 'products/'."\u{0420}\u{044B}\u{0431}\u{0451}\u{0448}\u{043A}\u{0438}".'/sdfsdfs (1).png';
        $data = $this->payload();
        $data['groups'][0]['items'][0]['images'] = [$path];
        DB::table('subdivisions')->where('id', 1)->update(['logo' => $path]);
        $outlay = $this->save($data);
        $expected = 'http://localhost/storage/images/products/'.rawurlencode("\u{0420}\u{044B}\u{0431}\u{0451}\u{0448}\u{043A}\u{0438}").'/sdfsdfs%20%281%29.png';

        $image = $outlay->groups->first()->items->first()->images->first();
        $this->assertSame($path, $image->path);
        $this->assertSame($expected, $image->toArray()['url']);
        $this->getJson('/api/pub/outlays/'.$outlay->public_uuid)->assertOk()
            ->assertJsonPath('data.item.subdivision.logo', $path)
            ->assertJsonPath('data.item.subdivision.logo_url', $expected)
            ->assertJsonPath('data.item.groups.0.items.0.images.0.path', $path)
            ->assertJsonPath('data.item.groups.0.items.0.images.0.url', $expected);

        $image->path = 'https://example.com/chair.jpg';
        $this->assertSame($image->path, $image->url);

        $subdivision = $outlay->subdivision;
        $this->assertSame($expected, $subdivision->toArray()['logo_url']);
        $subdivision->logo = null;
        $this->assertNull($subdivision->logo_url);
        $subdivision->logo = 'https://example.com/logo.png';
        $this->assertSame($subdivision->logo, $subdivision->logo_url);
    }

    public function test_foreign_item_and_excessive_discount_roll_back(): void
    {
        $first = $this->save($this->payload());
        $second = $this->save($this->payload());
        $data = $this->payload();
        $data['groups'][0]['id'] = $second->groups->first()->id;
        $data['groups'][0]['items'][0]['id'] = $first->groups->first()->items->first()->id;
        try {
            $this->save($data, $second);
            $this->fail('Foreign item was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('groups.0.items.0.id', $e->errors());
        }
        $data = $this->payload();
        $data['discount_amount'] = '1000';
        try {
            $this->save($data);
            $this->fail('Excessive discount was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('discount_amount', $e->errors());
        }
        $this->assertDatabaseCount('outlays', 2);
        $this->assertDatabaseCount('outlay_items', 4);
    }

    public function test_deleting_outlay_cascades_to_its_composition(): void
    {
        $outlay = $this->save($this->payload());
        $outlay->delete();
        $this->assertDatabaseCount('outlay_groups', 0);
        $this->assertDatabaseCount('outlay_items', 0);
        $this->assertDatabaseCount('outlay_item_images', 0);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('outlay_group_names', 1);
    }

    public function test_list_does_not_require_subdivision(): void
    {
        $request = ListOutlaysRequest::create('/', 'POST');

        $this->assertTrue(Validator::make([], $request->rules())->passes());
    }

    public function test_delete_requires_matching_subdivision(): void
    {
        $outlay = $this->save($this->payload());
        $request = DeleteOutlayRequest::create('/', 'DELETE');
        $request->setRouteResolver(fn () => new class($outlay)
        {
            public function __construct(private readonly Outlay $outlay) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'outlay' ? $this->outlay : $default;
            }
        });

        $this->assertTrue(Validator::make([], $request->rules())->errors()->has('subdivision_id'));
        $this->assertTrue(Validator::make(['subdivision_id' => 2], $request->rules())->errors()->has('subdivision_id'));
        $this->assertTrue(Validator::make(['subdivision_id' => 1], $request->rules())->passes());
    }

    public function test_prepayment_data_is_saved_and_cleared(): void
    {
        $data = $this->payload();
        $data['prepayment'] = true;
        $data['prepayment_amount'] = '5.25';
        $data['prepayment_date'] = '2026-09-30 12:30:00';
        $data['prepayment_cash_box_id'] = 1;

        $outlay = $this->save($data);

        $this->assertTrue($outlay->prepayment);
        $this->assertSame('5.25', $outlay->prepayment_amount);
        $this->assertSame('2026-09-30 12:30:00', $outlay->prepayment_date->format('Y-m-d H:i:s'));
        $this->assertSame(1, $outlay->prepayment_method_id);

        $data['prepayment'] = false;
        $updated = $this->save($data, $outlay);

        $this->assertNull($updated->prepayment_amount);
        $this->assertNull($updated->prepayment_date);
        $this->assertNull($updated->prepayment_method_id);
    }

    public function test_prepayment_requires_details_and_cannot_exceed_total(): void
    {
        $data = $this->payload();
        $data['prepayment'] = true;
        $request = OutlayRequest::create('/', 'POST', $data);
        $errors = Validator::make($data, $request->rules())->errors();

        $this->assertTrue($errors->has('prepayment_amount'));
        $this->assertTrue($errors->has('prepayment_date'));
        $this->assertTrue($errors->has('prepayment_cash_box_id'));

        $data['prepayment_amount'] = '1000.00';
        $data['prepayment_date'] = '2026-09-30 12:30:00';
        $data['prepayment_cash_box_id'] = 1;

        try {
            $this->save($data);
            $this->fail('Excessive prepayment was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('prepayment_amount', $e->errors());
        }

        $this->assertDatabaseCount('outlays', 0);
    }

    public function test_non_rental_stock_is_reconciled_by_difference_and_restored_on_delete(): void
    {
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        DB::table('products')->where('id', 1)->update(['is_rent' => false]);

        $outlay = $this->save($this->payload());
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 8]);
        $this->assertDatabaseCount('stock_movements', 2);

        $data = $this->payload();
        $data['groups'][0]['id'] = $outlay->groups->first()->id;
        $data['groups'][0]['items'] = [[
            'id' => $outlay->groups->first()->items->first()->id,
            'product_id' => 1, 'price' => '10.00', 'quantity' => '1',
        ]];
        $outlay = $this->save($data, $outlay);
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 9]);

        $this->save($data, $outlay);
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 9]);

        $this->deleteOutlay($outlay);
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 10]);
    }

    public function test_rental_stock_is_reserved_for_overlapping_dates(): void
    {
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        $outlay = $this->save($this->payload());

        $this->assertDatabaseCount('product_rentals', 2);
        $this->assertSame(
            '8.000',
            app(\App\Modules\Admin\Warehouses\Services\WarehouseService::class)
                ->availability(1, 1, '2026-10-01 12:00:00', '2026-10-02 12:00:00')
        );

        $data = $this->payload();
        $data['groups'][0]['items'] = [[
            'product_id' => 1, 'price' => '10.00', 'quantity' => '9',
        ]];

        try {
            $this->save($data);
            $this->fail('Unavailable rental quantity was accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('groups', $e->errors());
        }

        $this->assertDatabaseCount('outlays', 1);
        $this->deleteOutlay($outlay);
        $this->assertDatabaseMissing('product_rentals', ['outlay_id' => $outlay->id, 'status' => 'reserved']);
    }

    public function test_cash_ledger_is_idempotent_and_reverses_payments(): void
    {
        $data = $this->payload();
        $data['prepayment'] = true;
        $data['prepayment_amount'] = '5.25';
        $data['prepayment_date'] = '2026-09-30 12:30:00';
        $data['prepayment_cash_box_id'] = 1;
        $data['is_paid'] = true;
        $data['date_of_paid'] = '2026-10-01';
        $data['payment_cash_box_id'] = 1;

        $outlay = $this->save($data);
        $this->assertDatabaseHas('cash_boxes', ['id' => 1, 'balance' => 18]);
        $this->assertDatabaseCount('cash_box_transactions', 2);

        $outlay = $this->save($data, $outlay);
        $this->assertDatabaseHas('cash_boxes', ['id' => 1, 'balance' => 18]);
        $this->assertDatabaseCount('cash_box_transactions', 2);

        $data['is_paid'] = false;
        $outlay = $this->save($data, $outlay);
        $this->assertDatabaseHas('cash_boxes', ['id' => 1, 'balance' => 5.25]);

        $this->deleteOutlay($outlay);
        $this->assertDatabaseHas('cash_boxes', ['id' => 1, 'balance' => 0]);
        $this->assertDatabaseCount('cash_box_transactions', 4);
    }

    public function test_outlay_can_use_cash_box_from_another_subdivision(): void
    {
        DB::table('cash_boxes')->insert([
            'id' => 2, 'title' => 'Shared', 'subdivision_id' => 2, 'balance' => 0,
            'opening_balance' => 0, 'payment_method_id' => 1, 'sort_order' => 2,
            'is_dashboard' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $data = $this->payload();
        $data['prepayment'] = true;
        $data['prepayment_amount'] = '5.00';
        $data['prepayment_date'] = '2026-09-30 12:30:00';
        $data['prepayment_cash_box_id'] = 2;
        $data['is_paid'] = true;
        $data['date_of_paid'] = '2026-10-01';
        $data['payment_cash_box_id'] = 2;

        $outlay = $this->save($data);

        $this->assertSame(2, $outlay->prepayment_cash_box_id);
        $this->assertSame(2, $outlay->payment_cash_box_id);
        $this->assertDatabaseHas('cash_boxes', ['id' => 2, 'balance' => 18]);
    }

    public function test_batch_availability_excludes_own_rentals_and_uses_peak_occupancy(): void
    {
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        foreach ([['09:00:00', '11:00:00', 4], ['13:00:00', '15:00:00', 6], ['10:00:00', '14:00:00', 2]] as [$from, $to, $quantity]) {
            ProductRental::query()->create([
                'product_id' => 1, 'subdivision_id' => 1, 'quantity' => $quantity,
                'starts_at' => '2026-10-01 '.$from, 'ends_at' => '2026-10-01 '.$to, 'status' => 'reserved',
            ]);
        }
        $data = $this->payload();
        $data['execution_date'] = '2026-10-01 09:00:00';
        $data['return_date'] = '2026-10-01 16:00:00';
        $data['groups'][0]['items'] = [['product_id' => 1, 'price' => '10.00', 'quantity' => 2]];
        $outlay = $this->save($data);
        $service = app(WarehouseService::class);

        $result = $service->batchAvailability([1], 1, $data['execution_date'], $data['return_date'], $outlay->id)[0];
        $this->assertSame('10.000', $result['total_quantity']);
        $this->assertSame('8.000', $result['occupied_quantity']);
        $this->assertSame('2.000', $result['available_quantity']);
        $this->assertSame('0.000', $service->availability(1, 1, $data['execution_date'], $data['return_date']));
        $this->assertSame('2.000', $service->availability(1, 1, $data['execution_date'], $data['return_date'], $outlay->id));
    }

    public function test_batch_availability_counts_touching_boundaries_and_ignores_finished_rentals(): void
    {
        foreach ([['reserved', '10:00:00', '12:00:00', 3], ['issued', '12:00:00', '14:00:00', 4],
            ['returned', '10:00:00', '14:00:00', 10], ['cancelled', '10:00:00', '14:00:00', 10],
            ['reserved', '15:00:00', '16:00:00', 10]] as [$status, $from, $to, $quantity]) {
            ProductRental::query()->create([
                'product_id' => 1, 'subdivision_id' => 1, 'quantity' => $quantity,
                'starts_at' => '2026-10-01 '.$from, 'ends_at' => '2026-10-01 '.$to, 'status' => $status,
            ]);
        }
        $result = app(WarehouseService::class)->batchAvailability([1], 1, '2026-10-01 10:00:00', '2026-10-01 14:00:00')[0];
        $this->assertSame('7.000', $result['occupied_quantity']);
        $this->assertSame('3.000', $result['available_quantity']);
    }

    public function test_sale_availability_restores_own_consumption_and_allows_replacing_lines(): void
    {
        DB::table('statuses')->where('id', 1)->update(['affects_stock' => true]);
        DB::table('products')->where('id', 1)->update(['is_rent' => false]);
        $data = $this->payload();
        $data['groups'][0]['items'] = [['product_id' => 1, 'price' => '10.00', 'quantity' => 8]];
        $outlay = $this->save($data);
        $service = app(WarehouseService::class);
        $this->assertSame('2.000', $service->availability(1, 1, $data['execution_date'], $data['return_date']));
        $this->assertSame('10.000', $service->availability(1, 1, $data['execution_date'], $data['return_date'], $outlay->id));

        $outlay = $this->save($data, $outlay);
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 2]);
        $data['groups'][0]['items'][0]['quantity'] = 7;
        $outlay = $this->save($data, $outlay);
        $this->assertSame('10.000', $service->availability(1, 1, $data['execution_date'], $data['return_date'], $outlay->id));
        $this->assertDatabaseHas('product_stocks', ['product_id' => 1, 'quantity' => 3]);
    }

    public function test_batch_availability_is_unrestricted_for_services_and_unlimited_products(): void
    {
        $service = app(WarehouseService::class);
        DB::table('products')->where('id', 1)->update(['unlimited' => true]);
        $result = $service->batchAvailability([1], 1, '2026-10-01', '2026-10-02')[0];
        $this->assertTrue($result['unlimited']);
        $this->assertNull($result['available_quantity']);
        DB::table('products')->where('id', 1)->update(['unlimited' => false, 'is_service' => true]);
        $this->assertNull($service->availability(1, 1, '2026-10-01', '2026-10-02'));
    }

    public function test_batch_availability_rejects_foreign_products_and_outlays(): void
    {
        $outlay = $this->save($this->payload());
        $request = BatchAvailabilityRequest::create('/', 'POST', [
            'subdivision_id' => 2, 'product_ids' => [1], 'starts_at' => '2026-10-01',
            'ends_at' => '2026-10-02', 'outlay_id' => $outlay->id,
        ]);
        $this->assertTrue(Validator::make($request->all(), $request->rules())->errors()->has('outlay_id'));
        $this->expectException(ValidationException::class);
        app(WarehouseService::class)->batchAvailability([1], 2, '2026-10-01', '2026-10-02');
    }

    public function test_batch_availability_query_count_does_not_grow_with_products(): void
    {
        $productRows = [];
        $categoryRows = [];
        for ($id = 2; $id <= 101; $id++) {
            $productRows[] = ['id' => $id, 'title' => 'Product '.$id, 'is_rent' => true];
            $categoryRows[] = ['product_id' => $id, 'category_id' => 1];
        }
        DB::table('products')->insert($productRows);
        DB::table('category_product')->insert($categoryRows);
        $service = app(WarehouseService::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->batchAvailability([1], 1, '2026-10-01', '2026-10-02');
        $singleCount = count(DB::getQueryLog());
        DB::flushQueryLog();
        $items = $service->batchAvailability(range(1, 101), 1, '2026-10-01', '2026-10-02');
        $batchCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(101, $items);
        $this->assertSame($singleCount, $batchCount);
        $this->assertSame(3, $batchCount);
        $this->assertSame('10.000', $items[100]['available_quantity']);
    }

    public function test_batch_availability_endpoint_validates_input_and_subdivision_access(): void
    {
        $user = new class extends User
        {
            public function canDo($alias, $require = false)
            {
                return is_array($alias) && in_array('OUTLAYS_ACCESS', $alias, true);
            }

            public function hasSubdivisionAccess(int|string|null $subdivisionId): bool
            {
                return (int) $subdivisionId === 1;
            }
        };
        $user->id = 1;
        $this->actingAs($user, 'api');
        $payload = ['subdivision_id' => 1, 'product_ids' => [1],
            'starts_at' => '2026-10-01 10:00:00', 'ends_at' => '2026-10-02 18:00:00'];
        $this->postJson('/api/admin/products/availability', $payload)
            ->assertOk()->assertJsonPath('data.items.0.available_quantity', '10.000');
        $this->getJson('/api/admin/products/availability?'.http_build_query([
            'subdivision_id' => 1, 'product_id' => 1,
            'starts_at' => $payload['starts_at'], 'ends_at' => $payload['ends_at'],
        ]))->assertOk()->assertJsonPath('data.quantity', '10.000');
        $this->postJson('/api/admin/products/availability', array_replace($payload, ['subdivision_id' => 2]))
            ->assertForbidden();
        $this->postJson('/api/admin/products/availability', array_replace($payload, ['product_ids' => [1, 1]]))
            ->assertUnprocessable();
        $this->postJson('/api/admin/products/availability', array_replace($payload, ['ends_at' => null]))
            ->assertUnprocessable();
        $this->postJson('/api/admin/products/availability', array_replace($payload, ['ends_at' => '2026-09-01']))
            ->assertUnprocessable();
        DB::table('products')->where('id', 1)->update(['is_rent' => false]);
        $this->postJson('/api/admin/products/availability', array_replace($payload, ['ends_at' => null]))
            ->assertOk()->assertJsonPath('data.items.0.available_quantity', '10.000');
    }

    public function test_product_without_description_or_category_can_be_read(): void
    {
        DB::table('product_description')->where('product_id', 1)->delete();
        DB::table('category_product')->where('product_id', 1)->delete();

        $result = app(ProductsService::class)->getProduct(Product::findOrFail(1));

        $this->assertSame(1, $result['id']);
        $this->assertNull($result['description']);
        $this->assertNull($result['category_id']);
    }

    public function test_product_quantity_initializes_and_adjusts_warehouse_stock(): void
    {
        $request = ProductsRequest::create('/', 'POST', [
            'title' => 'Table', 'quantity' => 4, 'category_id' => 1, 'is_rent' => false,
        ]);
        $request->setUserResolver(fn () => new class
        {
            public int $id = 1;

            public function hasSubdivisionAccess($id): bool
            {
                return (int) $id === 1;
            }
        });

        $product = app(ProductsService::class)->save($request, new Product);
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $product->id, 'subdivision_id' => 1, 'quantity' => 4,
        ]);

        $update = ProductsRequest::create('/', 'PUT', [
            'title' => 'Table', 'quantity' => 6, 'category_id' => 1, 'is_rent' => false,
        ]);
        $update->setUserResolver($request->getUserResolver());
        app(ProductsService::class)->save($update, $product);

        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $product->id, 'subdivision_id' => 1, 'quantity' => 6,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'source_type' => 'manual', 'type' => 'manual_adjustment', 'quantity' => 2,
        ]);
    }
}

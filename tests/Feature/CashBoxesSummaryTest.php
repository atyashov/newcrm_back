<?php

namespace Tests\Feature;

use App\Modules\Admin\CashBoxes\Services\CashBoxesSummaryService;
use App\Modules\Admin\User\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashBoxesSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (['users', 'subdivisions', 'payment_methods'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->increments('id'));
            DB::table($name)->insert(['id' => 1]);
        }
        DB::table('subdivisions')->insert(['id' => 2]);
        Schema::table('payment_methods', fn (Blueprint $table) => $table->string('title')->default('Cash'));
        foreach ([
            'CashBoxes/Migrations/2026_07_26_132355_create_cash_boxes_table.php',
            'CashBoxes/Migrations/2026_09_26_000004_create_cash_ledger.php',
            'ExpenseGroups/Migrations/2026_08_01_064126_create_expense_groups_table.php',
            'Expenses/Migrations/2026_08_04_071309_create_expenses_table.php',
            'Borrowings/Migrations/2026_08_05_143355_create_borrowings_table.php',
        ] as $path) {
            (require app_path('Modules/Admin/'.$path))->up();
        }
        Schema::create('outlays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->default('Event');
            $table->unsignedInteger('payment_method_id')->nullable();
            $table->unsignedInteger('prepayment_method_id')->nullable();
            $table->unsignedInteger('subdivision_id');
            $table->dateTime('execution_date');
            $table->decimal('total_cost', 14, 2);
            $table->boolean('prepayment')->default(false);
            $table->decimal('prepayment_amount', 14, 2)->nullable();
            $table->dateTime('prepayment_date')->nullable();
            $table->unsignedBigInteger('prepayment_cash_box_id')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('date_of_paid')->nullable();
            $table->unsignedBigInteger('payment_cash_box_id')->nullable();
        });
        DB::table('cash_boxes')->insert([
            ['id' => 1, 'title' => 'Cash', 'balance' => 999, 'opening_balance' => 999, 'payment_method_id' => 1, 'sort_order' => 1, 'is_dashboard' => true],
            ['id' => 2, 'title' => 'Bank', 'balance' => 0, 'opening_balance' => 0, 'payment_method_id' => 1, 'sort_order' => 2, 'is_dashboard' => false],
        ]);
    }

    private function outlay(array $values): int
    {
        return DB::table('outlays')->insertGetId($values + [
            'subdivision_id' => 1, 'execution_date' => '2026-10-15 12:00:00', 'total_cost' => 100,
        ]);
    }

    private function signIn(bool $permission = true, array $subdivisions = [1]): void
    {
        $user = new class extends User
        {
            public bool $allowed = true;

            public array $subdivisionIds = [1];

            public function canDo($alias, $require = false)
            {
                return $this->allowed && count(array_intersect(['CAASHBOX_ACCESS', 'OUTLAYS_ACCESS'], (array) $alias)) > 0;
            }

            public function hasSubdivisionAccess(int|string|null $subdivisionId): bool
            {
                return in_array((int) $subdivisionId, $this->subdivisionIds, true);
            }

            public function getAvailableSubdivisionIds(): array
            {
                return $this->subdivisionIds;
            }
        };
        $user->id = 1;
        $user->allowed = $permission;
        $user->subdivisionIds = $subdivisions;
        $this->actingAs($user, 'api');
    }

    public function test_summary_aggregates_entire_period_and_forecast_with_date_boundaries(): void
    {
        $this->outlay([
            'prepayment' => true, 'prepayment_amount' => 20, 'prepayment_date' => '2026-09-30 23:59:59',
            'prepayment_cash_box_id' => 1, 'is_paid' => true,
            'date_of_paid' => '2026-10-01', 'payment_cash_box_id' => 2,
        ]);
        $this->outlay([
            'execution_date' => '2026-11-01 00:00:00', 'prepayment' => true,
            'prepayment_amount' => 30, 'prepayment_date' => '2026-10-31 23:59:59', 'prepayment_cash_box_id' => 1,
        ]);
        $this->outlay(['total_cost' => 50]);
        $this->outlay(['subdivision_id' => 2, 'total_cost' => 999, 'is_paid' => true, 'date_of_paid' => '2026-10-01', 'payment_cash_box_id' => 1]);
        $this->outlay(['execution_date' => '2026-11-01', 'prepayment' => true, 'prepayment_amount' => 999, 'prepayment_date' => '2026-11-01', 'prepayment_cash_box_id' => 1]);

        DB::table('expense_groups')->insert(['id' => 1, 'title' => 'Materials']);
        DB::table('expenses')->insert([
            ['date' => '2026-10-01', 'amount' => 10, 'price' => 10, 'quantity' => 1, 'subdivision_id' => 1, 'expense_group_id' => 1, 'cash_box_id' => 1],
            ['date' => '2026-10-31', 'amount' => 5, 'price' => 5, 'quantity' => 1, 'subdivision_id' => 1, 'expense_group_id' => 1, 'cash_box_id' => 2],
            ['date' => '2026-10-15', 'amount' => 999, 'price' => 999, 'quantity' => 1, 'subdivision_id' => 2, 'expense_group_id' => 1, 'cash_box_id' => 1],
        ]);
        DB::table('borrowings')->insert([
            ['title' => 'In', 'date' => '2026-10-01', 'amount' => 7, 'subdivision_sender_id' => 2, 'subdivision_receiver_id' => 1, 'cash_box_sender_id' => 2, 'cash_box_receiver_id' => 1],
            ['title' => 'Out', 'date' => '2026-10-31', 'amount' => 3, 'subdivision_sender_id' => 1, 'subdivision_receiver_id' => 2, 'cash_box_sender_id' => 2, 'cash_box_receiver_id' => 1],
        ]);
        $this->signIn();
        $response = $this->getJson('/api/admin/cash-boxes/summary?subdivision_id=1&dateStartFrom=2026-10-01&dateStartTo=2026-10-31&page=999&per_page=1');
        $response->assertOk()->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.opening_balance', '20.00')
            ->assertJsonPath('data.items.0.cash_box_opening_balance', '999.00')
            ->assertJsonPath('data.items.0.outlay_income', '30.00')
            ->assertJsonPath('data.items.0.expenses', '10.00')
            ->assertJsonPath('data.items.0.borrowing_received', '7.00')
            ->assertJsonPath('data.items.0.closing_balance', '47.00')
            ->assertJsonPath('data.items.1.outlay_income', '80.00')
            ->assertJsonPath('data.items.1.borrowing_sent', '3.00')
            ->assertJsonPath('data.items.1.closing_balance', '72.00')
            ->assertJsonPath('data.totals.closing_balance', '119.00')
            ->assertJsonPath('data.totals.outlays_total', '150.00')
            ->assertJsonPath('data.totals.outlays_forecast', '160.00')
            ->assertJsonPath('data.totals.forecast_closing_balance', '169.00');
    }

    public function test_permissions_subdivision_and_period_validation(): void
    {
        $url = '/api/admin/cash-boxes/summary';
        $this->getJson($url)->assertUnauthorized();
        $this->signIn(false);
        $this->getJson($url.'?subdivision_id=1')->assertForbidden();
        $this->signIn();
        $params = ['subdivision_id' => 1, 'date_start_from' => '2026-10-01', 'date_start_to' => '2026-10-31'];
        $this->getJson($url.'?'.http_build_query(array_replace($params, ['subdivision_id' => 2])))->assertForbidden();
        foreach ([['subdivision_id' => null], ['date_start_to' => '2026-09-01'], ['date_start_from' => 'nonsense']] as $invalid) {
            $this->getJson($url.'?'.http_build_query(array_replace($params, $invalid)))->assertUnprocessable();
        }
        $this->getJson($url.'?'.http_build_query($params))->assertOk()->assertJsonPath('data.totals.closing_balance', '0.00');
    }

    public function test_query_count_is_constant_and_edits_do_not_double_count(): void
    {
        $id = $this->outlay(['is_paid' => true, 'payment_cash_box_id' => 1, 'date_of_paid' => '2026-10-01']);
        $service = app(CashBoxesSummaryService::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame('100.00', $service->summary(1, '2026-10-01', '2026-10-31')['totals']['outlay_income']);
        $this->assertCount(3, DB::getQueryLog());
        DB::disableQueryLog();
        for ($i = 0; $i < 20; $i++) {
            DB::table('cash_boxes')->insert(['title' => 'Empty '.$i, 'balance' => 0, 'payment_method_id' => 1, 'sort_order' => $i + 3, 'is_dashboard' => false]);
        }
        DB::table('outlays')->where('id', $id)->update(['total_cost' => 75]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame('75.00', $service->summary(1, '2026-10-01', '2026-10-31')['totals']['outlay_income']);
        $this->assertCount(3, DB::getQueryLog());
        DB::disableQueryLog();
        DB::table('outlays')->where('id', $id)->delete();
        $this->assertSame('0.00', $service->summary(1, '2026-10-01', '2026-10-31')['totals']['closing_balance']);
    }

    public function test_receipts_operations_and_summary_agree_across_pages_and_sources(): void
    {
        $this->outlay(['title' => 'November event', 'execution_date' => '2026-11-10 12:00:00', 'total_cost' => '1000.00',
            'prepayment' => true, 'prepayment_amount' => '300.00', 'prepayment_date' => '2026-10-01 23:59:59', 'prepayment_cash_box_id' => 1,
            'is_paid' => true, 'date_of_paid' => '2026-10-31', 'payment_cash_box_id' => 2]);
        $this->outlay(['total_cost' => '50.00', 'prepayment' => true, 'prepayment_amount' => '50.00',
            'prepayment_date' => '2026-10-31 23:59:59', 'prepayment_cash_box_id' => 1, 'is_paid' => true, 'date_of_paid' => '2026-10-31', 'payment_cash_box_id' => 1]);
        $this->outlay(['subdivision_id' => 2, 'is_paid' => true, 'date_of_paid' => '2026-10-15', 'payment_cash_box_id' => 1, 'total_cost' => 999]);
        DB::table('expense_groups')->insert(['id' => 1, 'title' => 'Materials']);
        DB::table('expenses')->insert(['date' => '2026-10-31', 'amount' => '20.01', 'price' => '20.01', 'quantity' => 1, 'subdivision_id' => 1, 'expense_group_id' => 1, 'cash_box_id' => 1]);
        DB::table('borrowings')->insert([
            ['title' => 'Transfer in', 'date' => '2026-10-01', 'amount' => 7, 'subdivision_sender_id' => 2, 'subdivision_receiver_id' => 1, 'cash_box_sender_id' => 2, 'cash_box_receiver_id' => 1],
            ['title' => 'Transfer out', 'date' => '2026-10-31', 'amount' => 3, 'subdivision_sender_id' => 1, 'subdivision_receiver_id' => 2, 'cash_box_sender_id' => 2, 'cash_box_receiver_id' => 1],
        ]);
        $this->signIn();
        $period = '?date_from=2026-10-01&date_to=2026-10-31';
        $receipts = $this->getJson('/api/admin/outlays/receipts'.$period.'&page_size=1&sort=amount')->assertOk()
            ->assertJsonPath('data.items.total', 3)->assertJsonPath('data.totals.amount', '1050.00');
        $this->getJson('/api/admin/outlays/receipts'.$period.'&page_size=1&page=2')->assertOk()->assertJsonPath('data.totals.amount', '1050.00');
        $this->getJson('/api/admin/outlays/receipts'.$period.'&execution_date_from=2026-11-01')->assertOk()->assertJsonPath('data.totals.amount', '1000.00');
        $this->getJson('/api/admin/outlays/receipts'.$period.'&cash_box_id=2')->assertOk()->assertJsonPath('data.totals.amount', '700.00');
        $operations = $this->getJson('/api/admin/cash-boxes/operations'.$period.'&page_size=1')->assertOk()
            ->assertJsonPath('data.items.total', 6)->assertJsonPath('data.totals.income', '1057.00')
            ->assertJsonPath('data.totals.expense', '23.01')->assertJsonPath('data.totals.net', '1033.99');
        $summary = app(CashBoxesSummaryService::class)->summary(1, '2026-10-01', '2026-10-31');
        $this->assertSame($summary['totals']['outlay_income'], $receipts->json('data.totals.amount'));
        $this->assertSame($summary['totals']['net_change'], $operations->json('data.totals.net'));
        $this->getJson('/api/admin/cash-boxes/operations'.$period.'&type=borrowing_sent')->assertOk()
            ->assertJsonPath('data.items.data.0.signed_amount', '-3.00')->assertJsonPath('data.items.data.0.amount', '3.00');
        $this->getJson('/api/admin/cash-boxes/operations'.$period.'&type=borrowing_received')->assertOk()->assertJsonPath('data.items.data.0.signed_amount', '7.00');
        $this->getJson('/api/admin/outlays/receipts'.$period.'&subdivision_id=2')->assertForbidden();
        $this->getJson('/api/admin/cash-boxes/operations'.$period.'&subdivision_id=2')->assertForbidden();
        $this->getJson('/api/admin/outlays/receipts?date_to=2026-10-31')->assertOk();
        $this->getJson('/api/admin/cash-boxes/operations?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/admin/outlays/receipts?type=expense')->assertUnprocessable();

        $this->signIn(true, [1, 2]);
        $this->getJson('/api/admin/cash-boxes/operations'.$period.'&source_type=borrowing')
            ->assertOk()->assertJsonPath('data.items.total', 4)->assertJsonPath('data.totals.net', '0.00');
    }

    public function test_financial_rows_follow_document_edits_and_do_not_use_ledger_deltas(): void
    {
        $id = $this->outlay(['total_cost' => '1000.00', 'prepayment' => true, 'prepayment_amount' => '300.00', 'prepayment_date' => '2026-10-01',
            'prepayment_cash_box_id' => 1, 'is_paid' => true, 'payment_cash_box_id' => 1, 'date_of_paid' => '2026-10-31']);
        $this->signIn();
        $url = '/api/admin/outlays/receipts?date_from=2026-10-01&date_to=2026-10-31';
        $key = $this->getJson($url.'&type=outlay_payment')->assertOk()->assertJsonPath('data.totals.amount', '700.00')->json('data.items.data.0.key');
        DB::table('outlays')->where('id', $id)->update(['total_cost' => '1200.00', 'payment_cash_box_id' => 2]);
        $this->getJson($url.'&type=outlay_payment')->assertOk()->assertJsonPath('data.totals.amount', '900.00')
            ->assertJsonPath('data.items.data.0.cash_box.id', 2)->assertJsonPath('data.items.data.0.key', $key);
        DB::table('outlays')->where('id', $id)->update(['date_of_paid' => '2026-11-01']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.totals.amount', '300.00');
        DB::table('outlays')->where('id', $id)->update(['prepayment' => false]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.total', 0)->assertJsonPath('data.totals.amount', '0.00');
        DB::table('outlays')->where('id', $id)->update(['date_of_paid' => '2026-10-31']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.totals.amount', '1200.00');
        DB::table('outlays')->where('id', $id)->update(['is_paid' => false]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.items.total', 0);
        DB::table('outlays')->where('id', $id)->delete();
        $this->getJson($url)->assertOk()->assertJsonPath('data.totals.amount', '0.00');
    }

    public function test_financial_query_count_is_constant_and_permissions_are_enforced(): void
    {
        $this->outlay(['is_paid' => true, 'date_of_paid' => '2026-10-01', 'payment_cash_box_id' => 1]);
        $request = \App\Modules\Admin\CashBoxes\Requests\ListCashOperationsRequest::create('/', 'GET', ['page_size' => 100]);
        $request->setUserResolver(fn () => new class
        {
            public function getAvailableSubdivisionIds(): array
            {
                return [1];
            }
        });
        $service = app(\App\Modules\Admin\CashBoxes\Services\CashOperationsService::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $service->list($request);
        $this->assertCount(3, DB::getQueryLog());
        DB::disableQueryLog();
        for ($i = 0; $i < 30; $i++) {
            $this->outlay(['is_paid' => true, 'date_of_paid' => '2026-10-01', 'payment_cash_box_id' => 1]);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(31, $service->list($request)['items']->total());
        $this->assertCount(3, DB::getQueryLog());
        DB::disableQueryLog();
        $this->getJson('/api/admin/outlays/receipts')->assertUnauthorized();
        $this->getJson('/api/admin/cash-boxes/operations')->assertUnauthorized();
        $this->signIn(false);
        $this->getJson('/api/admin/outlays/receipts')->assertForbidden();
        $this->getJson('/api/admin/cash-boxes/operations')->assertForbidden();
    }
}

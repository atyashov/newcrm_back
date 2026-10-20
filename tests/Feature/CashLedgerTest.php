<?php

namespace Tests\Feature;

use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Requests\CashBoxesRequest;
use App\Modules\Admin\CashBoxes\Services\CashBoxesService;
use App\Modules\Admin\CashBoxes\Services\CashLedgerService;
use App\Modules\Admin\Expenses\Models\Expense;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CashLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('subdivisions', fn (Blueprint $table) => $table->increments('id'));
        Schema::create('payment_methods', fn (Blueprint $table) => $table->increments('id'));
        DB::table('users')->insert(['id' => 1]);
        DB::table('subdivisions')->insert([['id' => 1], ['id' => 2]]);
        DB::table('payment_methods')->insert(['id' => 1]);

        foreach ([
            'CashBoxes/Migrations/2026_07_26_132355_create_cash_boxes_table.php',
            'CashBoxes/Migrations/2026_09_26_000004_create_cash_ledger.php',
            'ExpenseGroups/Migrations/2026_08_01_064126_create_expense_groups_table.php',
            'Expenses/Migrations/2026_08_04_071309_create_expenses_table.php',
            'Borrowings/Migrations/2026_08_05_143355_create_borrowings_table.php',
        ] as $path) {
            (require app_path('Modules/Admin/'.$path))->up();
        }

        CashBox::query()->insert([
            [
                'id' => 1, 'title' => 'Sender', 'subdivision_id' => 1, 'balance' => 100,
                'opening_balance' => 100, 'payment_method_id' => 1, 'sort_order' => 1,
                'is_dashboard' => false, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 2, 'title' => 'Receiver', 'subdivision_id' => 2, 'balance' => 50,
                'opening_balance' => 50, 'payment_method_id' => 1, 'sort_order' => 1,
                'is_dashboard' => false, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function test_cash_box_can_be_created_without_subdivision(): void
    {
        $data = [
            'title' => 'Shared', 'opening_balance' => '12.50',
            'payment_method_id' => 1, 'sort_order' => 3, 'is_dashboard' => false,
        ];
        $request = CashBoxesRequest::create('/', 'POST', $data);
        $this->assertTrue(Validator::make($data, $request->rules())->passes());

        $cashBox = app(CashBoxesService::class)->save($request, new CashBox);

        $this->assertNull($cashBox->subdivision_id);
        $this->assertSame('12.50', $cashBox->balance);
    }

    public function test_expense_uses_difference_postings_and_reverses_on_delete(): void
    {
        DB::table('expense_groups')->insert([
            'id' => 1, 'title' => 'Materials', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $expense = Expense::query()->create([
            'date' => '2026-10-01', 'price' => 25, 'quantity' => 1, 'amount' => 25,
            'subdivision_id' => 1, 'expense_group_id' => 1, 'cash_box_id' => 1,
        ]);
        $ledger = app(CashLedgerService::class);

        $ledger->syncExpense($expense, 1);
        $ledger->syncExpense($expense, 1);
        $this->assertSame('75.00', CashBox::find(1)->balance);
        $this->assertDatabaseCount('cash_box_transactions', 1);

        $expense->amount = 30;
        $expense->save();
        $ledger->syncExpense($expense, 1);
        $this->assertSame('70.00', CashBox::find(1)->balance);

        $ledger->removeExpense($expense, 1);
        $this->assertSame('100.00', CashBox::find(1)->balance);
    }

    public function test_borrowing_moves_money_between_cash_boxes_idempotently(): void
    {
        $borrowing = Borrowing::query()->create([
            'title' => 'Transfer', 'amount' => 20, 'date' => '2026-10-01',
            'subdivision_sender_id' => 1, 'subdivision_receiver_id' => 2,
            'cash_box_sender_id' => 1, 'cash_box_receiver_id' => 2,
        ]);
        $ledger = app(CashLedgerService::class);

        $ledger->syncBorrowing($borrowing, 1);
        $ledger->syncBorrowing($borrowing, 1);
        $this->assertSame('80.00', CashBox::find(1)->balance);
        $this->assertSame('70.00', CashBox::find(2)->balance);
        $this->assertDatabaseCount('cash_box_transactions', 2);

        $borrowing->amount = 30;
        $borrowing->save();
        $ledger->syncBorrowing($borrowing, 1);
        $this->assertSame('70.00', CashBox::find(1)->balance);
        $this->assertSame('80.00', CashBox::find(2)->balance);

        $ledger->removeBorrowing($borrowing, 1);
        $this->assertSame('100.00', CashBox::find(1)->balance);
        $this->assertSame('50.00', CashBox::find(2)->balance);
    }
}

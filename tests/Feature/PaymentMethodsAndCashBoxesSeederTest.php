<?php

namespace Tests\Feature;

use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\PaymentMethods\Models\PaymentMethod;
use Database\Seeders\PaymentMethodsAndCashBoxesSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentMethodsAndCashBoxesSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('subdivisions', fn (Blueprint $table) => $table->increments('id'));
        foreach ([
            'PaymentMethods/Migrations/2022_12_13_101933_create_payment_methods_table.php',
            'CashBoxes/Migrations/2026_07_26_132355_create_cash_boxes_table.php',
            'CashBoxes/Migrations/2026_09_26_000004_create_cash_ledger.php',
        ] as $path) {
            (require app_path('Modules/Admin/'.$path))->up();
        }
    }

    public function test_seeder_creates_linked_entries_in_requested_order_without_duplicates(): void
    {
        $this->seed(PaymentMethodsAndCashBoxesSeeder::class);
        $before = CashBox::query()->orderBy('id')->get()->toArray();
        $this->seed(PaymentMethodsAndCashBoxesSeeder::class);

        $this->assertDatabaseCount('payment_methods', 26);
        $this->assertDatabaseCount('cash_boxes', 26);
        $this->assertSame($before, CashBox::query()->orderBy('id')->get()->toArray());
        $cashBoxes = CashBox::query()->with('paymentMethod')->orderBy('sort_order')->get();
        $this->assertSame('Нал', $cashBoxes->first()->title);
        $this->assertSame('СБЦ', $cashBoxes->last()->title);
        foreach ($cashBoxes as $position => $cashBox) {
            $this->assertSame($cashBox->title, $cashBox->paymentMethod->title);
            $this->assertSame($position + 1, $cashBox->sort_order);
            $this->assertSame('0.00', $cashBox->balance);
            $this->assertSame('0.00', $cashBox->opening_balance);
            $this->assertTrue($cashBox->is_dashboard);
            $this->assertNull($cashBox->subdivision_id);
        }
    }

    public function test_seeder_preserves_existing_balances_settings_and_payment_method_link(): void
    {
        $method = PaymentMethod::create(['title' => 'Нал']);
        $other = PaymentMethod::create(['title' => 'Existing method']);
        $cashBox = new CashBox();
        $cashBox->forceFill([
            'title' => 'Нал', 'payment_method_id' => $other->id,
            'balance' => '1234.56', 'opening_balance' => '1000.00',
            'sort_order' => 99, 'is_dashboard' => false,
        ])->save();
        $before = $cashBox->fresh()->toArray();

        $this->seed(PaymentMethodsAndCashBoxesSeeder::class);

        $this->assertSame($before, $cashBox->fresh()->toArray());
        $this->assertSame($method->id, PaymentMethod::where('title', 'Нал')->sole()->id);
        $this->assertDatabaseCount('cash_boxes', 26);
        $this->assertDatabaseCount('payment_methods', 27);
    }
}

<?php

namespace Database\Seeders;

use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\PaymentMethods\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodsAndCashBoxesSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            'Нал',
            'ВЛ',
            'ИП Леви',
            'Карта МБ',
            'МБЛ',
            'ИПЛ накопит',
            'Т-банк ВЛ',
            'ООО',
            'Т-банк ООО',
            'Т-банк ЕЧ',
            'Точка банк ЕЧ',
            'ЕЧ',
            'ВМ',
            'ИП Маркер',
            'ИП Кунцевич',
            'ЕР',
            'ДШ',
            'ИП спец',
            'АК',
            'Точка Банк ВМ',
            'АН',
            'Карта СБ',
            'Т-банк Дойняк',
            'ООО МБ',
            'ДД',
            'СБЦ',
        ];

        DB::transaction(function () use ($titles) {
            foreach ($titles as $position => $title) {
                $paymentMethod = PaymentMethod::query()->firstOrCreate(['title' => $title]);
                $cashBox = CashBox::query()->firstOrNew(['title' => $title]);

                if (! $cashBox->exists) {
                    $cashBox->forceFill([
                        'payment_method_id' => $paymentMethod->id,
                        'opening_balance' => 0,
                        'balance' => 0,
                        'sort_order' => $position + 1,
                        'is_dashboard' => true,
                    ])->save();
                }
            }
        });
    }
}

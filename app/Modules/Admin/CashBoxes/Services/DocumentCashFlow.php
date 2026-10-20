<?php

namespace App\Modules\Admin\CashBoxes\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DocumentCashFlow
{
    public function payments(array $subdivisionIds): Builder
    {
        $outlays = DB::table('outlays')->whereIn('subdivision_id', $subdivisionIds);
        $remainder = 'total_cost - CASE WHEN prepayment = 1 THEN COALESCE(prepayment_amount, 0) ELSE 0 END';
        $common = "'outlay' AS source_type, id AS source_id, title AS source_title, id AS outlay_id, title AS outlay_title, subdivision_id, execution_date";

        return (clone $outlays)->where('prepayment', true)->where('prepayment_amount', '>', 0)->whereNotNull('prepayment_cash_box_id')->whereNotNull('prepayment_date')
            ->selectRaw("$common, prepayment_date AS occurred_at, prepayment_cash_box_id AS cash_box_id, prepayment_method_id AS payment_method_id, 'outlay_prepayment' AS type, 'outlay_income' AS kind, 'income' AS direction, CAST(ROUND(prepayment_amount * 100, 0) AS SIGNED) AS signed_cents")
            ->unionAll((clone $outlays)->where('is_paid', true)->whereRaw("($remainder) > 0")->whereNotNull('payment_cash_box_id')->whereNotNull('date_of_paid')
                ->selectRaw("$common, date_of_paid AS occurred_at, payment_cash_box_id AS cash_box_id, payment_method_id, 'outlay_payment' AS type, 'outlay_income' AS kind, 'income' AS direction, CAST(ROUND(($remainder) * 100, 0) AS SIGNED) AS signed_cents"));
    }

    public function events(array $subdivisionIds): Builder
    {
        $borrowing = DB::table('borrowings')->where('amount', '>', 0);

        return $this->payments($subdivisionIds)
            ->unionAll(DB::table('expenses')->leftJoin('expense_groups', 'expense_groups.id', '=', 'expenses.expense_group_id')
                ->whereIn('expenses.subdivision_id', $subdivisionIds)->where('expenses.amount', '>', 0)
                ->selectRaw("'expense' AS source_type, expenses.id AS source_id, expense_groups.title AS source_title, NULL AS outlay_id, NULL AS outlay_title, expenses.subdivision_id, NULL AS execution_date, expenses.date AS occurred_at, expenses.cash_box_id, NULL AS payment_method_id, 'expense' AS type, 'expense' AS kind, 'expense' AS direction, -CAST(ROUND(expenses.amount * 100, 0) AS SIGNED) AS signed_cents"))
            ->unionAll((clone $borrowing)->whereIn('subdivision_receiver_id', $subdivisionIds)
                ->selectRaw("'borrowing' AS source_type, id AS source_id, title AS source_title, NULL AS outlay_id, NULL AS outlay_title, subdivision_receiver_id AS subdivision_id, NULL AS execution_date, date AS occurred_at, cash_box_receiver_id AS cash_box_id, NULL AS payment_method_id, 'borrowing_received' AS type, 'borrowing_received' AS kind, 'income' AS direction, CAST(ROUND(amount * 100, 0) AS SIGNED) AS signed_cents"))
            ->unionAll((clone $borrowing)->whereIn('subdivision_sender_id', $subdivisionIds)
                ->selectRaw("'borrowing' AS source_type, id AS source_id, title AS source_title, NULL AS outlay_id, NULL AS outlay_title, subdivision_sender_id AS subdivision_id, NULL AS execution_date, date AS occurred_at, cash_box_sender_id AS cash_box_id, NULL AS payment_method_id, 'borrowing_sent' AS type, 'borrowing_sent' AS kind, 'expense' AS direction, -CAST(ROUND(amount * 100, 0) AS SIGNED) AS signed_cents"));
    }

    public static function cents(mixed $value): int
    {
        return BigDecimal::of((string) ($value ?? 0))->toScale(2, RoundingMode::HALF_UP)->multipliedBy(100)->toInt();
    }

    public static function money(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Modules\Admin\CashBoxes\Services;

use App\Modules\Admin\CashBoxes\Models\CashBox;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CashBoxesSummaryService
{
    public function __construct(private readonly DocumentCashFlow $cashFlow) {}

    public function summary(int $subdivisionId, string $from, string $to): array
    {
        $start = $from;
        $end = CarbonImmutable::parse($to)->addDay()->format('Y-m-d');

        $events = $this->cashFlow->events([$subdivisionId]);

        // Aggregate current documents, not journal deltas: edits and reversals must not count twice.
        $query = DB::query()->fromSub($events, 'events')->where('occurred_at', '<', $end)
            ->select('cash_box_id')
            ->selectRaw('SUM(CASE WHEN occurred_at < ? THEN signed_cents ELSE 0 END) AS opening_balance', [$start])
            ->selectRaw('SUM(CASE WHEN occurred_at >= ? THEN signed_cents ELSE 0 END) AS net_change', [$start]);
        foreach (['outlay_income', 'expense', 'borrowing_received', 'borrowing_sent'] as $kind) {
            $query->selectRaw("SUM(CASE WHEN occurred_at >= ? AND kind = ? THEN signed_cents ELSE 0 END) AS {$kind}", [$start, $kind]);
        }
        $query->selectRaw("SUM(CASE WHEN kind = 'outlay_income' AND occurred_at < ? AND execution_date >= ? AND execution_date < ? THEN signed_cents ELSE 0 END) AS paid_before_period", [$start, $start, $end])
            ->selectRaw("SUM(CASE WHEN kind = 'outlay_income' AND occurred_at >= ? AND (execution_date < ? OR execution_date >= ?) THEN signed_cents ELSE 0 END) AS other_period_income", [$start, $start, $end]);
        $aggregates = $query->groupBy('cash_box_id')->get()->keyBy('cash_box_id');

        $keys = ['opening_balance', 'outlay_income', 'expenses', 'borrowing_received', 'borrowing_sent', 'net_change', 'closing_balance'];
        $totals = array_fill_keys($keys, 0);
        $paidBefore = 0;
        $otherIncome = 0;
        $items = [];
        foreach (CashBox::query()->orderBy('sort_order')->orderBy('id')->get() as $cashBox) {
            $row = $aggregates->get($cashBox->id);
            $values = [];
            foreach ($keys as $key) {
                $values[$key] = match ($key) {
                    'expenses' => -(int) ($row?->expense ?? 0),
                    'borrowing_sent' => -(int) ($row?->borrowing_sent ?? 0),
                    'closing_balance' => (int) ($row?->opening_balance ?? 0) + (int) ($row?->net_change ?? 0),
                    default => (int) ($row?->{$key} ?? 0),
                };
                $totals[$key] += $values[$key];
            }
            $paidBefore += (int) ($row?->paid_before_period ?? 0);
            $otherIncome += (int) ($row?->other_period_income ?? 0);
            $items[] = [
                'id' => $cashBox->id, 'title' => $cashBox->title,
                'payment_method_id' => $cashBox->payment_method_id,
                'is_dashboard' => $cashBox->is_dashboard,
                'cash_box_opening_balance' => $cashBox->opening_balance,
                ...array_map($this->money(...), $values),
            ];
        }

        $outlayTotal = $this->cents(DB::table('outlays')->where('subdivision_id', $subdivisionId)
            ->where('execution_date', '>=', $start)->where('execution_date', '<', $end)->sum('total_cost'));
        $totals['outlays_total'] = $outlayTotal;
        $totals['outlays_forecast'] = $outlayTotal - $paidBefore + $otherIncome;
        $totals['forecast_closing_balance'] = $totals['opening_balance'] + $totals['outlays_forecast']
            - $totals['expenses'] + $totals['borrowing_received'] - $totals['borrowing_sent'];

        return [
            'subdivision_id' => $subdivisionId,
            'date_start_from' => $from, 'date_start_to' => $to,
            'items' => $items,
            'totals' => array_map($this->money(...), $totals),
        ];
    }

    private function cents(mixed $value): int
    {
        return DocumentCashFlow::cents($value);
    }

    private function money(int $cents): string
    {
        return DocumentCashFlow::money($cents);
    }
}

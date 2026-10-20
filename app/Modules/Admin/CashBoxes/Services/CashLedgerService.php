<?php

namespace App\Modules\Admin\CashBoxes\Services;

use App\Modules\Admin\Borrowings\Models\Borrowing;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Models\CashBoxTransaction;
use App\Modules\Admin\Expenses\Models\Expense;
use App\Modules\Admin\Outlays\Models\Outlay;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CashLedgerService
{
    public function syncOutlay(Outlay $outlay, ?int $userId): void
    {
        $desired = [];

        if ($outlay->prepayment && (float) $outlay->prepayment_amount > 0) {
            $desired[] = [
                'cash_box_id' => $outlay->prepayment_cash_box_id,
                'source_line' => 'prepayment',
                'type' => 'outlay_prepayment',
                'amount' => $this->cents($outlay->prepayment_amount),
                'occurred_at' => $outlay->prepayment_date,
            ];
        }

        if ($outlay->is_paid) {
            $prepayment = $outlay->prepayment ? $this->cents($outlay->prepayment_amount) : 0;
            $remainder = $this->cents($outlay->total_cost) - $prepayment;

            if ($remainder > 0) {
                $desired[] = [
                    'cash_box_id' => $outlay->payment_cash_box_id,
                    'source_line' => 'final_payment',
                    'type' => 'outlay_payment',
                    'amount' => $remainder,
                    'occurred_at' => $outlay->date_of_paid,
                ];
            }
        }

        $this->reconcile('outlay', $outlay->id, $desired, $userId);
    }

    public function removeOutlay(Outlay $outlay, ?int $userId): void
    {
        $this->reconcile('outlay', $outlay->id, [], $userId);
    }

    public function syncExpense(Expense $expense, ?int $userId): void
    {
        $this->reconcile('expense', $expense->id, [[
            'cash_box_id' => $expense->cash_box_id,
            'source_line' => 'expense',
            'type' => 'expense',
            'amount' => -$this->cents($expense->amount),
            'occurred_at' => $expense->date,
        ]], $userId);
    }

    public function removeExpense(Expense $expense, ?int $userId): void
    {
        $this->reconcile('expense', $expense->id, [], $userId);
    }

    public function syncBorrowing(Borrowing $borrowing, ?int $userId): void
    {
        $amount = $this->cents($borrowing->amount);

        $this->reconcile('borrowing', $borrowing->id, [
            [
                'cash_box_id' => $borrowing->cash_box_sender_id,
                'source_line' => 'sender',
                'type' => 'borrowing_transfer',
                'amount' => -$amount,
                'occurred_at' => $borrowing->date,
            ],
            [
                'cash_box_id' => $borrowing->cash_box_receiver_id,
                'source_line' => 'receiver',
                'type' => 'borrowing_transfer',
                'amount' => $amount,
                'occurred_at' => $borrowing->date,
            ],
        ], $userId);
    }

    public function removeBorrowing(Borrowing $borrowing, ?int $userId): void
    {
        $this->reconcile('borrowing', $borrowing->id, [], $userId);
    }

    private function reconcile(string $sourceType, int $sourceId, array $desiredRows, ?int $userId): void
    {
        $desired = collect($desiredRows)
            ->filter(fn (array $row) => ! empty($row['cash_box_id']) && $row['amount'] !== 0)
            ->keyBy(fn (array $row) => $this->key($row['cash_box_id'], $row['source_line']));

        $existingRows = CashBoxTransaction::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();
        $cashBoxIds = $desired->pluck('cash_box_id')
            ->merge($existingRows->pluck('cash_box_id'))->unique()->values();
        $cashBoxes = CashBox::query()->whereKey($cashBoxIds)->lockForUpdate()->get()->keyBy('id');

        $existingRows = CashBoxTransaction::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();
        $existing = $existingRows
            ->groupBy(fn (CashBoxTransaction $row) => $this->key($row->cash_box_id, $row->source_line))
            ->map(fn (Collection $rows) => $rows->sum(
                fn (CashBoxTransaction $row) => $row->direction === 'income'
                    ? $this->cents($row->amount)
                    : -$this->cents($row->amount)
            ));
        $keys = $desired->keys()->merge($existing->keys())->unique();

        foreach ($keys as $key) {
            $desiredRow = $desired->get($key);
            $desiredAmount = $desiredRow['amount'] ?? 0;
            $delta = $desiredAmount - ($existing->get($key) ?? 0);

            if ($delta === 0) {
                continue;
            }

            $cashBoxId = (int) explode(':', $key, 2)[0];
            $cashBox = $cashBoxes->get($cashBoxId);

            if (! $cashBox) {
                throw ValidationException::withMessages([
                    'cash_box_id' => 'Cash box does not exist.',
                ]);
            }

            $reference = $desiredRow ?? $this->referenceForKey($existingRows, $key);
            CashBoxTransaction::query()->create([
                'cash_box_id' => $cashBoxId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'source_line' => $reference['source_line'],
                'type' => $reference['type'],
                'direction' => $delta > 0 ? 'income' : 'expense',
                'amount' => $this->money(abs($delta)),
                'occurred_at' => $reference['occurred_at'] ?? now(),
                'user_id' => $userId,
                'comment' => $desiredRow ? null : 'Automatic reversal',
            ]);

            $cashBox->balance = $this->money($this->cents($cashBox->balance) + $delta);
            $cashBox->save();
        }
    }

    private function referenceForKey(Collection $rows, string $key): array
    {
        $row = $rows->first(
            fn (CashBoxTransaction $row) => $this->key($row->cash_box_id, $row->source_line) === $key
        );

        return [
            'source_line' => $row->source_line,
            'type' => $row->type,
            'occurred_at' => now(),
        ];
    }

    private function key(int $cashBoxId, string $sourceLine): string
    {
        return $cashBoxId.':'.$sourceLine;
    }

    private function cents(mixed $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) ($value ?? 0), 2), 2, '');
        $sign = str_starts_with($whole, '-') ? -1 : 1;
        $whole = ltrim($whole, '-');

        return $sign * ((int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0'));
    }

    private function money(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

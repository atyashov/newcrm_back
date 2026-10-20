<?php

namespace App\Modules\Admin\CashBoxes\Services;

use App\Core\Filters\Filter;
use App\Core\Traits\HasSorting;
use App\Modules\Admin\CashBoxes\Models\CashBox;
use App\Modules\Admin\CashBoxes\Requests\CashBoxesRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CashBoxesService
{
    use HasSorting;

    public function getCashBoxes(
        $pageSize,
        Filter $filter,
        $sort = null,
        array $subdivisionIds = [],
    ): LengthAwarePaginator {
        $result = CashBox::filter($filter)
            ->where(function ($query) use ($subdivisionIds) {
                $query->whereIn('subdivision_id', $subdivisionIds);
                $query->orWhereNull('subdivision_id');
            })
            ->with(['paymentMethod', 'subdivision']);

        if ($sort) {
            $result = $this->applySorting($result, $sort);
        } else {
            $result
                ->orderBy('sort_order')
                ->orderBy('title');
        }

        return $result->paginate($pageSize);
    }

    public function save(CashBoxesRequest $request, CashBox $cashBox): CashBox
    {
        return DB::transaction(function () use ($request, $cashBox) {
            if ($cashBox->exists) {
                $cashBox = CashBox::query()->lockForUpdate()->findOrFail($cashBox->id);
            }

            $oldOpeningBalance = $this->cents($cashBox->opening_balance);
            $cashBox->fill($request->only($cashBox->getFillable()));

            if (! $cashBox->exists) {
                $cashBox->balance = $cashBox->opening_balance;
            } else {
                $cashBox->balance = $this->money(
                    $this->cents($cashBox->balance)
                    + $this->cents($cashBox->opening_balance)
                    - $oldOpeningBalance
                );
            }

            $cashBox->save();

            return $cashBox;
        });
    }

    private function cents(mixed $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) ($value ?? 0), 2), 2, '');
        $sign = str_starts_with($whole, '-') ? -1 : 1;

        return $sign * ((int) ltrim($whole, '-') * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0'));
    }

    private function money(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

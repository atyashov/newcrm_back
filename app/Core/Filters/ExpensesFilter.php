<?php

namespace App\Core\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ExpensesFilter extends Filter
{
    public const array KEYS_TO_INT = [
        'subdivision_id',
        'expense_group_id',
        'cash_box_id',
        'quantity',
    ];

    public const array KEYS_TO_DATE = [
        'date',
        'date_from',
        'date_to',
    ];

    protected function date(CarbonImmutable $value): Builder
    {
        return $this->builder->whereDate('date', $value);
    }

    protected function dateFrom(CarbonImmutable $value): Builder
    {
        return $this->builder->whereDate('date', '>=', $value);
    }

    protected function dateTo(CarbonImmutable $value): Builder
    {
        return $this->builder->whereDate('date', '<=', $value);
    }

    protected function price(string|float|int $value): Builder
    {
        return $this->builder->where('price', (float)$value);
    }

    protected function quantity(int $value): Builder
    {
        return $this->builder->where('quantity', $value);
    }

    protected function amount(string|float|int $value): Builder
    {
        return $this->builder->where('amount', (float)$value);
    }

    protected function subdivisionId(int $value): Builder
    {
        return $this->builder->where('subdivision_id', $value);
    }

    protected function expenseGroupId(int $value): Builder
    {
        return $this->builder->where('expense_group_id', $value);
    }

    protected function cashBoxId(int $value): Builder
    {
        return $this->builder->where('cash_box_id', $value);
    }
}

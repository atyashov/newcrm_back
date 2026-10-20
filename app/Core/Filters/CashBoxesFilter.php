<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class CashBoxesFilter extends Filter
{
    public const array KEYS_TO_BOOL = [
        'is_dashboard',
    ];

    public const array KEYS_TO_INT = [
        'subdivision_id',
        'payment_method_id',
        'sort_order',
    ];

    protected function subdivisionId(int $value): Builder
    {
        return $this->builder->where('subdivision_id', $value);
    }

    protected function title(string $value): Builder
    {
        return $this->builder->where('title', 'like', '%' . urldecode($value) . '%');
    }

    protected function paymentMethodId(int $value): Builder
    {
        return $this->builder->where('payment_method_id', $value);
    }

    protected function isDashboard(bool $value): Builder
    {
        return $this->builder->where('is_dashboard', $value);
    }

    protected function sortOrder(int $value): Builder
    {
        return $this->builder->where('sort_order', $value);
    }
}

<?php

namespace App\Core\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class BorrowingsFilter extends Filter
{
    public const array KEYS_TO_INT = [
        'subdivision_sender_id',
        'subdivision_receiver_id',
        'cash_box_sender_id',
        'cash_box_receiver_id',
    ];

    public const array KEYS_TO_DATE = [
        'date',
        'date_from',
        'date_to',
    ];

    protected function title(string $value): Builder
    {
        return $this->builder->where('title', 'like', '%' . urldecode($value) . '%');
    }

    protected function signing(string $value): Builder
    {
        return $this->builder->where('signing', 'like', '%' . urldecode($value) . '%');
    }

    protected function amount(string|float|int $value): Builder
    {
        return $this->builder->where('amount', (float)$value);
    }

    protected function amountFrom(string|float|int $value): Builder
    {
        return $this->builder->where('amount', '>=', (float)$value);
    }

    protected function amountTo(string|float|int $value): Builder
    {
        return $this->builder->where('amount', '<=', (float)$value);
    }

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

    protected function subdivisionSenderId(int $value): Builder
    {
        return $this->builder->where('subdivision_sender_id', $value);
    }

    protected function subdivisionReceiverId(int $value): Builder
    {
        return $this->builder->where('subdivision_receiver_id', $value);
    }

    protected function cashBoxSenderId(int $value): Builder
    {
        return $this->builder->where('cash_box_sender_id', $value);
    }

    protected function cashBoxReceiverId(int $value): Builder
    {
        return $this->builder->where('cash_box_receiver_id', $value);
    }
}

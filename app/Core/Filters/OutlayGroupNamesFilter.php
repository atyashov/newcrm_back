<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class OutlayGroupNamesFilter extends Filter
{
    public const array KEYS_TO_INT = [
        'subdivision_id',
    ];

    protected function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', '%' . urldecode($value) . '%');
    }

    protected function subdivisionId(int $value): Builder
    {
        return $this->builder->where('subdivision_id', $value);
    }
}

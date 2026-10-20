<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class StoresFilter extends Filter
{
    protected function title(string $value): Builder
    {
        return $this->builder->where('title', 'like', '%' . urldecode($value) . '%');
    }
}

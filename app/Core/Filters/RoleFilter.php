<?php

namespace App\Core\Filters;

use App\Core\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class RoleFilter extends Filter
{
    protected function title(string $value): Builder
    {
        return $this->builder->where('title', 'like', '%' . $value . '%');
    }

    protected function created_at(string $value): Builder
    {
        return $this->builder->where('created_at', $value);
    }

    protected function updated_at(string $value): Builder
    {
        return $this->builder->where('updated_at', $value);
    }
}

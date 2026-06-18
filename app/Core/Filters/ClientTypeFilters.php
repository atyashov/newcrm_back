<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class ClientTypeFilters extends Filter
{

    protected function title(string $value): Builder
    {
        return $this->builder->where('type', 'like', "%" . $value . "%");
    }

    protected function createdAt(string $value): Builder
    {
        return $this->builder->where('created_at', 'like', "%" . $value . "%");
    }

}

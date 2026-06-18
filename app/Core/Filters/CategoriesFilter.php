<?php

namespace App\Core\Filters;

use App\Core\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CategoriesFilter extends Filter
{
    public const array KEYS_TO_INT = [
        'subdivision_id',
    ];

    public const array KEYS_TO_STRING = [
        'title'
    ];

    public function subdivisionId(int $subdivisionId): Builder
    {
        return $this->builder->where('subdivision_id', $subdivisionId);
    }

    public function title(string $title): Builder
    {
        return $this->builder->where('title', "like", "%" . $title . "%");
    }
}

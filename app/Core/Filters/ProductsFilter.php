<?php

namespace App\Core\Filters;

use App\Core\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ProductsFilter extends Filter
{
    public const array KEYS_TO_INT = [
        'subdivision_id',
        'category_id',
    ];

    public const array KEYS_TO_STRING = [
        'title'
    ];

    public function subdivisionId(int $subdivisionId): Builder
    {
        return $this->builder
            ->whereHas('categories', function (Builder $category) use ($subdivisionId) {
                $category->where('subdivision_id', $subdivisionId);
            });
    }

    public function categoryId(int $categoryId): Builder
    {
        return $this->builder->where('category_id', $categoryId);
    }

    public function title(string $title): Builder
    {
        return $this->builder->where('title', 'like', "%{$title}%");
    }
}

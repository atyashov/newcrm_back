<?php

namespace App\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasSorting
{
    /**
     * Применяет сортировку к запросу
     *
     * @param Builder $builder
     * @param string|null $sort Параметр сортировки (например: "name" или "-name" для DESC)
     * @return Builder
     */
    protected function applySorting(Builder $builder, ?string $sort): Builder
    {
        if (!$sort) {
            return $builder;
        }

        $sortType = "ASC";
        $sortField = $sort;

        if ($sort[0] === "-") {
            $sortType = "DESC";
            $sortField = substr($sort, 1);
        }

        return $builder->orderBy($sortField, $sortType);
    }
}


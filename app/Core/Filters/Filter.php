<?php

declare(strict_types=1);

namespace App\Core\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class Filter
{
    public const array KEYS_TO_BOOL = [];
    public const array KEYS_TO_INT = [];
    public const array KEYS_TO_DATE = [];
    public const array KEYS_STRING_TO_ARRAY = [];
    public const array KEYS_TO_ARRAY = [];

    protected Builder $builder;

    /**
     * @param Request $request
     */
    public function __construct(protected readonly Request $request)
    {
    }

    /**
     * Применение фильтров к запросу
     *
     * @param Builder $builder
     * @return Builder
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->input() as $method => $value) {
            $methodName = Str::camel($method);

            if (null === $value) {
                continue;
            }

            if (method_exists($this, $methodName)) {
                if (in_array($method, static::KEYS_TO_BOOL, true)) {
                    $value = (bool)$value;
                }

                if (in_array($method, static::KEYS_TO_INT, true)) {
                    $value = (int)$value;
                }

                if (in_array($method, static::KEYS_TO_DATE, true)) {
                    $value = CarbonImmutable::parse($value);
                }

                if (in_array($method, static::KEYS_TO_ARRAY, true)) {
                    $value = is_array($value) ? $value : [$value];
                }

                if (in_array($method, static::KEYS_STRING_TO_ARRAY, true)) {
                    $value = explode(',', $value);
                }

                $this->builder = $this->{$methodName}($value);
            }
        }

        return $this->builder;
    }
}

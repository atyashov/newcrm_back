<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class IndividualFilter extends Filter
{

    protected function name(string $value): Builder
    {
        return $this->builder->where('name', 'like', '%' . urldecode($value) . '%');
    }

    protected function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', '%' . $value . '%');
    }

    protected function phone(string $value): Builder
    {
        return $this->builder->where('phone', 'like', '%' . $value . '%');
    }

    protected function personalSale(int $value): Builder
    {
        return $this->builder->where('personal_sale', $value);
    }

    protected function address(string $value): Builder
    {
        return $this->builder->where('address', 'like', '%' . $value . '%');
    }

    protected function clientTypeId(int $value): Builder
    {
        return $this->builder->where('client_type_id', $value);
    }

}

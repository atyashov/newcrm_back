<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class LegalEntityFilter extends Filter
{

    protected function contactName(string $value): Builder
    {
        return $this->builder->where('contact_name', 'like', '%' . $value . '%');
    }

    protected function organizationName(string $value): Builder
    {
        return $this->builder->where('organization_name', 'like', '%' . $value . '%');
    }

    protected function mobilePhone(string $value): Builder
    {
        return $this->builder->where('mobile_phone', 'like', '%' . $value . '%');
    }

    protected function phone(string $value): Builder
    {
        return $this->builder->where('phone', 'like', '%' . $value . '%');
    }

    protected function personal_sale(int $value): Builder
    {
        return $this->builder->where('personal_sale', $value);
    }

    protected function legalAddress(string $value): Builder
    {
        return $this->builder->where('legal_address', 'like', '%' . $value . '%');
    }

    protected function email(string $value): Builder
    {
        return $this->builder->where('email', 'like', '%' . $value . '%');
    }

}

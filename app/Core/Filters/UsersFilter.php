<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class UsersFilter extends Filter
{
    protected function firstname(string $value): Builder
    {
        return $this->like('firstname', $value);
    }

    protected function lastname(string $value): Builder
    {
        return $this->like('lastname', $value);
    }

    protected function login(string $value): Builder
    {
        return $this->like('login', $value);
    }

    protected function email(string $value): Builder
    {
        return $this->like('email', $value);
    }

    protected function phone(string $value): Builder
    {
        return $this->like('phone', $value);
    }

    protected function status(string $value): Builder
    {
        return $this->builder->where('status', $value);
    }

    private function like(string $field, string $value): Builder
    {
        return $this->builder->where($field, 'like', '%'.$value.'%');
    }
}

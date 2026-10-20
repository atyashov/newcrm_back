<?php

namespace App\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class SubdivisionsFilter extends Filter
{
    protected function title(string $value): Builder
    {
        return $this->like('title', $value);
    }

    protected function amoIds(string $value): Builder
    {
        return $this->like('amo_ids', $value);
    }

    protected function smsSenderName(string $value): Builder
    {
        return $this->like('sms_sender_name', $value);
    }

    protected function siteLink(string $value): Builder
    {
        return $this->like('site_link', $value);
    }

    private function like(string $field, string $value): Builder
    {
        return $this->builder->where($field, 'like', '%'.$value.'%');
    }
}

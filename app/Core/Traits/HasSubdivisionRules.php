<?php

namespace App\Core\Traits;

trait HasSubdivisionRules
{
    protected function requiredSubdivisionRules(string $field = 'subdivision_id'): array
    {
        return [
            $field => ['required', 'integer', 'exists:subdivisions,id'],
        ];
    }

    protected function optionalSubdivisionRules(string $field = 'subdivision_id'): array
    {
        return [
            $field => ['nullable', 'integer', 'exists:subdivisions,id'],
        ];
    }

    protected function subdivisionId(string $field = 'subdivision_id'): ?int
    {
        $value = $this->input($field);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}

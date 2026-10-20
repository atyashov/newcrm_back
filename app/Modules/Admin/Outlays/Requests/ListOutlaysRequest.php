<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Modules\Admin\Outlays\Models\Outlay;
use App\Services\Requests\ApiRequest;

class ListOutlaysRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->optionalSubdivisionRules(),
            'id' => ['nullable', 'integer', 'min:1'],
            'self_pickup' => ['nullable', 'boolean'],
            'self_return' => ['nullable', 'boolean'],
            'is_rent' => ['nullable', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
            'prepayment' => ['nullable', 'boolean'],
            'return_date_from' => ['nullable', 'date'],
            'return_date_to' => ['nullable', 'date', ...($this->filled('return_date_from') ? ['after_or_equal:return_date_from'] : [])],
            'return_date' => ['nullable', 'date'],
            'execution_date' => ['nullable', 'date'],
            'date_of_paid' => ['nullable', 'date'],
            'return_date_comment' => ['nullable', 'string', 'max:65535'],
            'status_id' => ['nullable', 'integer', 'prohibits:status_ids'],
            'status_ids' => ['sometimes', 'array', 'max:500'],
            'status_ids.*' => ['integer', 'distinct', 'exists:statuses,id'],
            ...$this->arrayFilters(),
            'sort' => ['nullable', 'string', 'in:'.implode(',', collect(['id', 'total_cost', ...(new Outlay)->getFillable()])->reject(fn ($field) => $field === 'responsible_workers')->flatMap(fn ($field) => [$field, '-'.$field])->all())],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'date_start_from' => ['nullable', 'date'],
            'date_start_to' => ['nullable', 'date'],
            'dateStartFrom' => ['nullable', 'date'],
            'dateStartTo' => ['nullable', 'date'],
            'dateStart' => ['nullable', 'date'],
            'dateEnd' => ['nullable', 'date'],
            ...$this->legacyFilters(),
        ];
    }

    private function legacyFilters(): array
    {
        $rules = [];
        foreach (['title', 'date_comment', 'partner_entity', 'recipient_name', 'recipient_phone', 'address', 'route_link'] as $field) {
            $rules[$field] = ['nullable', 'string'];
        }
        foreach (['creator_id', 'client_type_id', 'client_id', 'source_id', 'subject_id', 'payment_method_id', 'prepayment_method_id', 'percent_discount'] as $field) {
            $rules[$field] = ['nullable', 'integer'];
        }
        foreach (['discount_amount', 'total_cost'] as $field) {
            $rules[$field] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('status_id') && $this->exists('status_ids')) {
                $validator->errors()->add('status_ids', 'Use either status_id or status_ids, not both.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        foreach (['self_pickup', 'self_return', 'is_rent', 'is_paid', 'prepayment'] as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $value = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($value !== null) {
                    $this->merge([$field => $value]);
                }
            }
        }
    }

    private function arrayFilters(): array
    {
        $rules = [];
        foreach ([...array_fill_keys(array_keys(Outlay::ASSIGNMENTS), 'users'), 'store_ids' => 'stores'] as $field => $table) {
            $rules[$field] = ['sometimes', 'array', 'max:500'];
            $rules[$field.'.*'] = ['integer', 'distinct', "exists:$table,id"];
        }

        return $rules;
    }
}

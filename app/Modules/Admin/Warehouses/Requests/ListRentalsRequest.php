<?php

namespace App\Modules\Admin\Warehouses\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ListRentalsRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'WAREHOUSE_ACCESS']);
    }

    public function rules(): array
    {
        return [
            ...$this->requiredSubdivisionRules(),
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'status' => ['nullable', Rule::in(['reserved', 'issued', 'returned', 'cancelled'])],
            'starts_from' => ['nullable', 'date'],
            'ends_to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}

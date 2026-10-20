<?php

namespace App\Modules\Admin\Warehouses\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ListStocksRequest extends ApiRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['id', '-id', 'quantity', '-quantity', 'title', '-title'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}

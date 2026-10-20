<?php

namespace App\Modules\Admin\Warehouses\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;

class AdjustStockRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'WAREHOUSE_MANAGE']);
    }

    public function rules(): array
    {
        return [
            ...$this->requiredSubdivisionRules(),
            'quantity_delta' => ['required', 'numeric', 'decimal:0,3', 'not_in:0,0.0,0.00,0.000'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

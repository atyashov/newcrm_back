<?php

namespace App\Modules\Admin\Warehouses\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class RentalStatusRequest extends ApiRequest
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
            'status' => ['required', Rule::in(['reserved', 'issued', 'returned', 'cancelled'])],
        ];
    }
}

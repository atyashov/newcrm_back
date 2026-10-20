<?php

namespace App\Modules\Admin\Products\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class AvailabilityRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo([
            'SUPER_ADMINISTRATOR', 'PRODUCTS_ACCESS', 'WAREHOUSE_ACCESS', 'OUTLAYS_ACCESS', 'OUTLAYS_MANAGE',
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->requiredSubdivisionRules(),
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'outlay_id' => ['nullable', 'integer',
                Rule::exists('outlays', 'id')->where('subdivision_id', $this->input('subdivision_id'))],
        ];
    }
}

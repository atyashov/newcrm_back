<?php

namespace App\Modules\Admin\Products\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class BatchAvailabilityRequest extends ApiRequest
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
            'product_ids' => ['required', 'array', 'min:1', 'max:1000'],
            'product_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'outlay_id' => ['nullable', 'integer',
                Rule::exists('outlays', 'id')->where('subdivision_id', $this->input('subdivision_id'))],
        ];
    }
}

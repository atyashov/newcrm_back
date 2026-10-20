<?php

namespace App\Modules\Admin\CashBoxes\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;

class CashBoxesSummaryRequest extends ApiRequest
{
    use HasSubdivisionRules;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_start_from' => $this->input('date_start_from') ?? $this->input('dateStartFrom') ?? $this->input('dateStart'),
            'date_start_to' => $this->input('date_start_to') ?? $this->input('dateStartTo') ?? $this->input('dateEnd'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'CAASHBOX_ACCESS']);
    }

    public function rules(): array
    {
        return [
            ...$this->requiredSubdivisionRules(),
            'date_start_from' => ['required', 'date_format:Y-m-d'],
            'date_start_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start_from'],
        ];
    }
}

<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;

class OutlayPeriodDocumentRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_ACCESS']);
    }

    public function rules(): array
    {
        return [
            ...$this->optionalSubdivisionRules(),
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}

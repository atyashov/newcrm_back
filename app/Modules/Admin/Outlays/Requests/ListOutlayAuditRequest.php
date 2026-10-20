<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\Outlays\Models\OutlayAuditEvent;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ListOutlayAuditRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('outlay'));
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'page_size' => ['sometimes', 'integer', 'between:1,100'],
            'event' => ['sometimes', Rule::in(OutlayAuditEvent::EVENTS)],
        ];
    }
}

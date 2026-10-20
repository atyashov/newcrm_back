<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Services\Requests\ApiRequest;

class UpdateOutlayStatusRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('outlay'));
    }

    public function rules(): array
    {
        return ['status_id' => ['required', 'integer', 'exists:statuses,id']];
    }
}

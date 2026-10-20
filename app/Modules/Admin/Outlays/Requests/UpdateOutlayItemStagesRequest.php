<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\OutlayGroups\Models\OutlayItemStage;
use App\Services\Requests\ApiRequest;

class UpdateOutlayItemStagesRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('outlay'));
    }

    public function rules(): array
    {
        return array_fill_keys(OutlayItemStage::FLAGS, ['sometimes', 'required', 'boolean']);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (collect(OutlayItemStage::FLAGS)->every(fn ($flag) => ! $this->exists($flag))) {
                $validator->errors()->add('stages', 'At least one stage flag is required.');
            }
        });
    }
}

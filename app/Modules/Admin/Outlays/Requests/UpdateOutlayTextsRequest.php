<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Services\Requests\ApiRequest;

abstract class UpdateOutlayTextsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('outlay'));
    }

    abstract protected function fields(): array;

    public function rules(): array
    {
        return array_fill_keys($this->fields(), ['sometimes', 'nullable', 'string', 'max:10000']);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (collect($this->fields())->every(fn ($field) => ! $this->exists($field))) {
                $validator->errors()->add('comments', 'At least one comment field is required.');
            }
        });
    }
}

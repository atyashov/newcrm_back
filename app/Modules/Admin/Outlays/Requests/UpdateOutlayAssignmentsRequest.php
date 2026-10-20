<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Services\Requests\ApiRequest;

class UpdateOutlayAssignmentsRequest extends ApiRequest
{
    use HasOutlayAssignments { withValidator as validateAssignments; }

    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('outlay'));
    }

    public function rules(): array
    {
        return $this->assignmentRules();
    }

    public function withValidator($validator): void
    {
        $this->validateAssignments($validator);
        $validator->after(function ($validator) {
            if (collect(array_keys(Outlay::ASSIGNMENTS))->every(fn ($field) => ! $this->exists($field))) {
                $validator->errors()->add('assignments', 'At least one assignment array is required.');
            }
        });
    }
}

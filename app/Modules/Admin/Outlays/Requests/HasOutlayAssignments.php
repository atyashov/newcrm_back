<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\Outlays\Models\Outlay;
use App\Modules\Admin\User\Models\User;

trait HasOutlayAssignments
{
    protected function assignmentRules(): array
    {
        $rules = [];
        foreach (Outlay::ASSIGNMENTS as $field => $relation) {
            $rules[$field] = ['sometimes', 'array', 'max:500'];
            $rules[$field.'.*'] = ['required', 'integer', 'distinct', 'exists:users,id'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $outlay = $this->route('outlay');
            $subdivisionId = $outlay instanceof Outlay ? $outlay->subdivision_id : $this->input('subdivision_id');
            if (! $this->user()->hasSubdivisionAccess($subdivisionId)) {
                abort(403);
            }
            $ids = collect(Outlay::ASSIGNMENTS)->keys()->flatMap(fn ($field) => $this->input($field, []))->unique();
            if ($ids->isEmpty()) {
                return;
            }
            $allowed = User::query()->whereKey($ids)->where('status', '1')
                ->whereHas('subdivisions', fn ($query) => $query->where('subdivisions.id', $subdivisionId))
                ->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (Outlay::ASSIGNMENTS as $field => $relation) {
                foreach ($this->input($field, []) as $index => $id) {
                    if (! in_array((int) $id, $allowed, true)) {
                        $validator->errors()->add("$field.$index", 'The user must be active and have access to the outlay subdivision.');
                    }
                }
            }
        });
    }
}

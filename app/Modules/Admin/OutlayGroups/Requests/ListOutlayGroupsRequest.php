<?php

namespace App\Modules\Admin\OutlayGroups\Requests;

use App\Core\Traits\HasSubdivisionRules;
use Illuminate\Foundation\Http\FormRequest;

class ListOutlayGroupsRequest extends FormRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_ACCESS']);
    }

    public function rules(): array
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->requiredSubdivisionRules(),
            'outlay_id' => ['required', 'integer', 'exists:outlays,id'],
        ];
    }
}

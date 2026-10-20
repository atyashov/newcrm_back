<?php

namespace App\Modules\Admin\OutlayGroups\Requests;

use App\Core\Traits\HasSubdivisionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OutlayGroupsRequest extends FormRequest
{
    use HasSubdivisionRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAY_ACCESS']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->requiredSubdivisionRules(),
            'name' => 'required',
        ];
    }
}

<?php

namespace App\Modules\Admin\OutlayGroupNames\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;

class OutlayGroupNamesRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_MANAGE']);
    }

    public function rules(): array
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->requiredSubdivisionRules(),
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}

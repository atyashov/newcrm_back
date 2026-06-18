<?php

namespace App\Modules\Admin\Clients\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class LegalEntityRequest extends FormRequest
{
    public function authorize() : bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'CLIENTS_ACCESS']);
    }

    public function rules() : array
    {
        return [
            'contact_name' => 'required',
            'organization_name' => 'required',
            'client_type_id' => 'required',
        ];
    }

}

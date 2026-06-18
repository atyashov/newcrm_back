<?php

namespace App\Modules\Admin\Clients\Requests;

use App\Services\Requests\ApiRequest;
use \Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class IndividualRequest extends ApiRequest
{
    public function authorize() : bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'CLIENTS_ACCESS']);
    }

    public function rules() : array
    {
        return [
            'name' => 'required',
            'client_type_id' => 'required',
        ];
    }
}

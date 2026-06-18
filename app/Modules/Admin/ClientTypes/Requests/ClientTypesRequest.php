<?php

namespace App\Modules\Admin\ClientTypes\Requests;

use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;

class ClientTypesRequest extends ApiRequest
{

    public function authorize() : bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'CLIENT_TYPES_ACCESS']);
    }

    public function rules() : array
    {
        return [
            'title' => 'required'
        ];
    }
}

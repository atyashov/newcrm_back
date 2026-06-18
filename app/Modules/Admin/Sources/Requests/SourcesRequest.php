<?php

namespace App\Modules\Admin\Sources\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SourcesRequest extends FormRequest
{
    public function authorize() : bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'SOURCES_ACCESS']);
    }

    public function rules() : array
    {
        return [
            'title' => 'required'
        ];
    }
}

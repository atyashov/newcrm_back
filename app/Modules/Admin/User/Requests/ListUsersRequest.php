<?php

namespace App\Modules\Admin\User\Requests;

use App\Services\Requests\ApiRequest;

class ListUsersRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'login' => ['nullable', 'string', 'max:90'],
            'email' => ['nullable', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:0,1'],
            'sort' => ['nullable', 'string', 'in:id,-id,firstname,-firstname,lastname,-lastname,login,-login,email,-email,phone,-phone,status,-status'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

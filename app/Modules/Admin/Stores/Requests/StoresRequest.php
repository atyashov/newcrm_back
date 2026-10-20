<?php

namespace App\Modules\Admin\Stores\Requests;

use App\Services\Requests\ApiRequest;

class StoresRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'STORE_MANAGE']);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255']];
    }
}

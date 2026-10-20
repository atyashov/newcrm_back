<?php

namespace App\Modules\Admin\Statuses\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'STATUSES_MANAGE']);
    }

    public function rules(): array
    {
        return [
            'title' => 'required',
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,id'],
            'affects_stock' => ['sometimes', 'boolean'],
        ];
    }
}

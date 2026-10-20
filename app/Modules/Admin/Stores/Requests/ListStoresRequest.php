<?php

namespace App\Modules\Admin\Stores\Requests;

use App\Services\Requests\ApiRequest;

class ListStoresRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:id,-id,title,-title,created_at,-created_at,updated_at,-updated_at'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

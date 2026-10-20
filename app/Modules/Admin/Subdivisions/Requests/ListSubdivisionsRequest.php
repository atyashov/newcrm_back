<?php

namespace App\Modules\Admin\Subdivisions\Requests;

use App\Services\Requests\ApiRequest;

class ListSubdivisionsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'amo_ids' => ['nullable', 'string', 'max:255'],
            'sms_sender_name' => ['nullable', 'string', 'max:255'],
            'site_link' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:id,-id,title,-title,amo_ids,-amo_ids,sms_sender_name,-sms_sender_name,site_link,-site_link'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

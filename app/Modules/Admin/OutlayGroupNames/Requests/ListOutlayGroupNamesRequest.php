<?php

namespace App\Modules\Admin\OutlayGroupNames\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;

class ListOutlayGroupNamesRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->requiredSubdivisionRules(),
            'name' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:id,-id,name,-name,created_at,-created_at'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

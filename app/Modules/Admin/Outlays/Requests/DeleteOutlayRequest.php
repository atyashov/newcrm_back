<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class DeleteOutlayRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_MANAGE']);
    }

    public function rules(): array
    {
        $outlay = $this->route('outlay');

        return [
            ...$this->requiredSubdivisionRules(),
            'subdivision_id' => [
                'required',
                'integer',
                'exists:subdivisions,id',
                Rule::in([(int) $outlay->subdivision_id]),
            ],
        ];
    }
}

<?php

namespace App\Modules\Admin\Products\Requests;

use App\Core\Traits\HasSubdivisionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProduuctsSimpleRequest extends FormRequest
{
    use HasSubdivisionRules;
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'PRODUCTS_MANAGE']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,id'],
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1'],
            'only_new' => ['nullable', 'boolean'],
            ...$this->requiredSubdivisionRules()
        ];
    }
}

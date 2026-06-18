<?php

namespace App\Modules\Admin\Products\Requests;

use App\Core\Traits\HasSubdivisionRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ListCategoriesRequest extends FormRequest
{
    use HasSubdivisionRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'PRODUCTS_ACCESS']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /** @see HasSubdivisionRules */
            ...$this->requiredSubdivisionRules(),
        ];
    }
}

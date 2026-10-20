<?php

namespace App\Modules\Admin\Products\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'PRODUCTS_MANAGE']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'store_id' => 'nullable|integer|exists:stores,id',
            'description' => 'string|nullable',
            'unlimited' => 'boolean|nullable',
            'is_rent' => 'boolean|nullable',
            'is_service' => 'boolean|nullable',
            'image' => 'string|nullable',
        ];
    }
}

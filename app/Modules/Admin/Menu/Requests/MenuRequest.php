<?php

namespace App\Modules\Admin\Menu\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'ROLES_ACCESS']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $this->merge(['menu' => $this->route('menu')]);

        return [
            'menu' => 'required|integer|exists:menus,id',
            'permissions' => 'required|array',
            'permissions.*' => 'required|integer|exists:permissions,id',
        ];
    }

    public function messages(): array
    {
        return [
            'menu.required' => 'Пункт меню не найден',
            'permissions.required' => 'Привилегии не получены',
            'permissions.array' => 'Неправильный тип данных',
            'permissions.*.required' => 'Вы пытаетесь скрыть меню от всех',
        ];
    }
}

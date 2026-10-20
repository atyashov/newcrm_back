<?php

namespace App\Modules\Admin\User\Requests;

use App\Modules\Admin\Role\Models\Role;
use App\Modules\Admin\User\Models\User;
use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'USER_MANAGE']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $user = $this->route('user');
        $loginUnique = Rule::unique('users', 'login');
        if ($user instanceof User) {
            $loginUnique->ignore($user);
        }

        return [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user instanceof User ? $user->id : null)],
            'login' => ['required', 'string', 'max:90', $loginUnique],
            'password' => ['bail', $user instanceof User ? 'nullable' : 'required', 'string', 'min:6', 'max:255', 'confirmed', function ($attribute, $value, $fail) {
                if (config('hashing.driver', 'bcrypt') === 'bcrypt' && strlen($value) > 72) {
                    $fail('The password must not exceed 72 bytes when using bcrypt.');
                }
            }],
            'phone' => 'required|string|max:255',
            'status' => ['sometimes', 'required', 'in:0,1'],
            'role_id' => 'required|integer|exists:roles,id',
            'subdivision_ids' => ['sometimes', 'array'],
            'subdivision_ids.*' => ['required', 'integer', 'distinct', 'exists:subdivisions,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty() || $this->user()->canDo('SUPER_ADMINISTRATOR')) {
                return;
            }
            $actor = $this->user();
            $role = Role::with('perms')->findOrFail($this->input('role_id'));
            $canAssign = fn ($role) => $role->alias !== 'SUPER_ADMINISTRATOR'
                && ! $role->perms->contains('alias', 'SUPER_ADMINISTRATOR')
                && ($actor->canDo('ROLES_MANAGE') || $role->perms->every(fn ($permission) => $actor->canDo($permission->alias)));
            if (! $canAssign($role)) {
                $validator->errors()->add('role_id', 'You cannot assign this role.');
            }
            foreach ($this->input('subdivision_ids', []) as $index => $id) {
                if (! $actor->hasSubdivisionAccess($id)) {
                    $validator->errors()->add("subdivision_ids.$index", 'You cannot assign this subdivision.');
                }
            }
            $target = $this->route('user');
            if ($target instanceof User) {
                if (! $target->roles()->with('perms')->get()->every($canAssign)) {
                    $validator->errors()->add('role_id', 'You cannot manage this user.');
                }
                if (! $target->subdivisions()->get()->every(fn ($subdivision) => $actor->hasSubdivisionAccess($subdivision->id))) {
                    $validator->errors()->add('subdivision_ids', 'You cannot manage a user outside your subdivisions.');
                }
            }
        });
    }
}

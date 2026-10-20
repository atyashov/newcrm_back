<?php

namespace App\Modules\Admin\ExpenseGroups\Requests;

use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;

class ExpenseGroupsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_GROUP_MANAGE']);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:expense_groups,id'],
        ];
    }
}

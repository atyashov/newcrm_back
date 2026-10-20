<?php

namespace App\Modules\Admin\Expenses\Requests;

use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpensesRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'EXPENSE_MANAGE']);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'min:0'],
            'subdivision_id' => ['required', 'integer', 'exists:subdivisions,id'],
            'expense_group_id' => ['required', 'integer', 'exists:expense_groups,id'],
            'cash_box_id' => [
                'required',
                'integer',
                Rule::exists('cash_boxes', 'id')->where('subdivision_id', $this->input('subdivision_id')),
            ],
        ];
    }
}

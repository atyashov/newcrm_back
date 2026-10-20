<?php

namespace App\Modules\Admin\CashBoxes\Requests;

use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;

class CashBoxesRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'CASHBOX_MANAGE']);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'decimal:0,2'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'sort_order' => ['required', 'integer'],
            'is_dashboard' => ['required', 'boolean'],
        ];
    }
}

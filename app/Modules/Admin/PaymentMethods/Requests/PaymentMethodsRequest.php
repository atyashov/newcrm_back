<?php

namespace App\Modules\Admin\PaymentMethods\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PaymentMethodsRequest extends FormRequest
{
    public function authorize() : bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'PAYMENT_METHODS_ACCESS']);
    }

    public function rules() : array
    {
        return [
            'title' => 'required'
        ];
    }
}

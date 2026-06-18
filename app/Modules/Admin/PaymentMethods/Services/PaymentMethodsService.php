<?php

namespace App\Modules\Admin\PaymentMethods\Services;

use App\Modules\Admin\PaymentMethods\Models\PaymentMethod;
use App\Modules\Admin\PaymentMethods\Requests\PaymentMethodsRequest;

class PaymentMethodsService
{
    public function getPaymentMethods(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentMethod::all();
    }

    public function save(PaymentMethodsRequest $request, PaymentMethod $paymentMethod) : PaymentMethod
    {
        $paymentMethod->fill($request->only($paymentMethod->getFillable()));
        $paymentMethod->save();
        return $paymentMethod;
    }

}

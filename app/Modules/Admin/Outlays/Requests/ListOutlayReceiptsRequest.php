<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Modules\Admin\CashBoxes\Requests\ListCashOperationsRequest;

class ListOutlayReceiptsRequest extends ListCashOperationsRequest
{
    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_ACCESS']);
    }

    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'type' => ['nullable', 'in:outlay_prepayment,outlay_payment'],
            'sort' => ['nullable', 'in:occurred_at,-occurred_at,amount,-amount,outlay_id,-outlay_id,execution_date,-execution_date'],
        ]);
    }
}

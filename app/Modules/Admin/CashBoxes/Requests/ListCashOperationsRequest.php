<?php

namespace App\Modules\Admin\CashBoxes\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;

class ListCashOperationsRequest extends ApiRequest
{
    use HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'CAASHBOX_ACCESS']);
    }

    public function rules(): array
    {
        return [
            ...$this->optionalSubdivisionRules(),
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'execution_date_from' => ['nullable', 'date_format:Y-m-d'],
            'execution_date_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('execution_date_from') ? ['after_or_equal:execution_date_from'] : [])],
            'type' => ['nullable', 'in:outlay_prepayment,outlay_payment,expense,borrowing_sent,borrowing_received'],
            'direction' => ['nullable', 'in:income,expense'],
            'cash_box_id' => ['nullable', 'integer', 'exists:cash_boxes,id'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'source_type' => ['nullable', 'in:outlay,expense,borrowing'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'outlay_id' => ['nullable', 'integer', 'min:1'],
            'outlay_title' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:occurred_at,-occurred_at,amount,-amount,type,-type,outlay_id,-outlay_id,execution_date,-execution_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}

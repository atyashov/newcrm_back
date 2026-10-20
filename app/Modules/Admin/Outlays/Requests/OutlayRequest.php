<?php

namespace App\Modules\Admin\Outlays\Requests;

use App\Core\Traits\HasSubdivisionRules;
use App\Services\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class OutlayRequest extends ApiRequest
{
    use HasOutlayAssignments, HasSubdivisionRules;

    public function authorize(): bool
    {
        return $this->user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAYS_MANAGE']);
    }

    public function rules(): array
    {
        return [
            ...$this->requiredSubdivisionRules(),
            ...$this->assignmentRules(),
            'self_pickup' => ['sometimes', 'boolean'],
            'self_return' => ['sometimes', 'boolean'],
            'return_date_comment' => ['nullable', 'string', 'max:65535'],
            'title' => ['required', 'string', 'max:255'],
            'execution_date' => ['required', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:execution_date'],
            'date_comment' => ['nullable', 'string'],
            'creator_id' => ['required', 'integer', 'exists:users,id'],
            'responsible_workers' => ['present', 'array'],
            'responsible_workers.*' => ['integer', 'distinct', 'exists:users,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'client_type_id' => ['nullable', 'integer', 'exists:client_types,id'],
            'client_id' => ['required', 'integer', 'min:1'],
            'partner_entity' => ['required', 'string', 'max:90'],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'is_rent' => ['sometimes', 'boolean'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:90'],
            'address' => ['nullable', 'string', 'max:255'],
            'percent_discount' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,100'],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,9999999999.99'],
            'is_paid' => ['sometimes', 'boolean'],
            'date_of_paid' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('is_paid')),
                'date',
            ],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'payment_cash_box_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('is_paid')),
                'integer',
                'exists:cash_boxes,id',
            ],
            'prepayment' => ['sometimes', 'boolean'],
            'prepayment_amount' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('prepayment')),
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:9999999999.99',
            ],
            'prepayment_date' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('prepayment')),
                'date',
            ],
            'prepayment_method_id' => [
                'nullable',
                'integer',
                'exists:payment_methods,id',
            ],
            'prepayment_cash_box_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('prepayment')),
                'integer',
                'exists:cash_boxes,id',
            ],
            'route_link' => ['nullable', 'string', 'max:255'],
            'groups' => ['present', 'array', 'max:100'],
            'groups.*' => ['array:id,outlay_group_name_id,items'],
            'groups.*.id' => ['nullable', 'integer', 'distinct'],
            'groups.*.outlay_group_name_id' => ['required', 'integer',
                Rule::exists('outlay_group_names', 'id')->where('subdivision_id', $this->input('subdivision_id'))],
            'groups.*.items' => ['present', 'array', 'max:1000'],
            'groups.*.items.*' => ['array:id,product_id,price,quantity,description,images'],
            'groups.*.items.*.id' => ['nullable', 'integer', 'distinct'],
            'groups.*.items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'groups.*.items.*.price' => ['required', 'numeric', 'decimal:0,2', 'between:0,9999999.99'],
            'groups.*.items.*.quantity' => ['required', 'integer', 'between:1,99999'],
            'groups.*.items.*.description' => ['nullable', 'string'],
            'groups.*.items.*.images' => ['sometimes', 'array', 'max:20'],
            'groups.*.items.*.images.*' => ['required', 'string', 'max:2048'],
        ];
    }
}

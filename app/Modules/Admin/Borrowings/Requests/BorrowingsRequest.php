<?php

namespace App\Modules\Admin\Borrowings\Requests;

use App\Services\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BorrowingsRequest extends ApiRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user->canDo(['SUPER_ADMINISTRATOR', 'BORROWING_MANAGE'])
            && $user->hasSubdivisionAccess($this->input('subdivision_sender_id'))
            && $user->hasSubdivisionAccess($this->input('subdivision_receiver_id'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'signing' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'subdivision_sender_id' => ['required', 'integer', 'exists:subdivisions,id'],
            'subdivision_receiver_id' => ['required', 'integer', 'exists:subdivisions,id'],
            'cash_box_sender_id' => [
                'required',
                'integer',
                Rule::exists('cash_boxes', 'id')->where('subdivision_id', $this->input('subdivision_sender_id')),
            ],
            'cash_box_receiver_id' => [
                'required',
                'integer',
                Rule::exists('cash_boxes', 'id')->where('subdivision_id', $this->input('subdivision_receiver_id')),
            ],
        ];
    }
}

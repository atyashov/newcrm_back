<?php

namespace App\Modules\Admin\Outlays\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OutlayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'OUTLAY_ACCESS']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'execution_date' => 'required',
            'subdivision_id' => 'required',
            'creator_id' => 'required',
            'implementation_officer' => 'required',
            'status_id' => 'required',
            'client_id' => 'required',
            'partner_entity' => 'required',
            'total_cost' => 'required',
        ];
    }
}

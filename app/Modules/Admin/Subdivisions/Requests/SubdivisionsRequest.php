<?php

namespace App\Modules\Admin\Subdivisions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SubdivisionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->canDo(['SUPER_ADMINISTRATOR', 'SUBDIVISIONS_MANAGE']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'amo_ids' => ['nullable', 'string', 'max:255', 'regex:/^\s*\d+(?:\s*,\s*\d+)*\s*$/'],
            'sms_sender_name' => ['nullable', 'string', 'max:255'],
            'site_link' => ['nullable', 'string', 'max:255'],
            'default_product_group_id_for_amo' => ['nullable', 'integer', Rule::exists('outlay_group_names', 'id')->where(function ($query) {
                if (! $this->user()->canDo('SUPER_ADMINISTRATOR')) {
                    $query->whereIn('subdivision_id', $this->user()->getAvailableSubdivisionIds());
                }
            })],
            'logo' => ['bail', 'nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (str_starts_with($value, '/') || str_contains($value, '\\') || str_contains($value, ':')
                    || str_contains($value, '%') || preg_match('/[\x00-\x1F]/', $value)
                    || in_array('..', explode('/', $value), true) || in_array('.', explode('/', $value), true)) {
                    $fail('Logo must be a relative image path from the file manager.');

                    return;
                }
                $disk = Storage::disk('images');
                if (! $disk->exists($value) || ! str_starts_with((string) $disk->mimeType($value), 'image/')) {
                    $fail('The selected image does not exist in the file manager.');
                }
            }],
        ];
    }
}

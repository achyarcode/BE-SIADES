<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuratApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_position' => 'nullable|array',
            'signed_pdf' => 'nullable|file|mimes:pdf|max:4096',
        ];
    }
}

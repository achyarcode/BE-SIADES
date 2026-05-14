<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuratIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:PENDING,DISETUJUI,DITOLAK',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuratRejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => 'nullable|string|max:1000',
        ];
    }
}

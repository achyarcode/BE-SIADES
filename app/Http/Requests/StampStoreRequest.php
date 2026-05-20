<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StampStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stamp_name' => 'required|string|max:255',
            'stamp_file' => 'required|file|mimes:png|mimetypes:image/png|max:2048',
        ];
    }
}

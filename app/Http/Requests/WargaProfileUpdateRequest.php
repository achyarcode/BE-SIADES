<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WargaProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['namaLengkap', 'username', 'nomorkk', 'alamat'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $normalized[$field] = trim($this->input($field));
            }
        }
        $this->merge($normalized);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'namaLengkap' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'nomorkk' => 'nullable|string|size:16',
            'alamat' => 'nullable|string',
        ];
    }
}

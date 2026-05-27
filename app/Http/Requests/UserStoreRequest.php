<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Map nomorKK (camelCase dari frontend) ke no_kk (snake_case untuk database)
        if ($this->has('nomorKK') && !$this->has('no_kk')) {
            $this->merge(['no_kk' => $this->input('nomorKK')]);
        }

        $normalized = [];

        foreach ([
            'namaLengkap',
            'username',
            'nik',
            'no_kk',
            'nomorKK',
            'nomorWA',
            'email',
            'rt',
            'rw',
            'alamat',
            'tempatLahir',
            'tanggalLahir',
            'mustUpdateCredentials',
        ] as $field) {
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
        return [
            'namaLengkap' => 'required|string|max:255',
            'username' => 'nullable|string|unique:users,username|max:255',
            'password' => 'nullable|string|min:6',
            'nik' => 'required|string|size:16|unique:users,nik',
            'no_kk' => 'nullable|string|size:16',
            'nomorWA' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenisKelamin' => 'required|in:L,P',
            'tempatLahir' => 'nullable|string|max:255',
            'tanggalLahir' => 'nullable|date',
            'mustUpdateCredentials' => 'nullable|boolean',
        ];
    }
}

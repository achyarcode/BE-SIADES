<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WargaSetupCredentialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
                'regex:/^(?!\d{16}$).+$/',
            ],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username tidak boleh berupa NIK 16 digit.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $password = (string) $this->input('password', '');

            if ($password !== '' && ! preg_match('/[a-zA-Z]/', $password)) {
                $validator->errors()->add('password', 'Password harus mengandung minimal 1 huruf.');
            }

            if ($password !== '' && ! preg_match('/[0-9]/', $password)) {
                $validator->errors()->add('password', 'Password harus mengandung minimal 1 angka.');
            }
        });
    }
}

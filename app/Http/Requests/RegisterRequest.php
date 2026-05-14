<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:6',
            'nik' => 'required|string|size:16|unique:users,nik',
            'no_kk' => 'nullable|string|size:16',
            'no_telp' => 'nullable|string|max:20',
            'jenisKelamin' => 'required|in:L,P',
        ];
    }
}

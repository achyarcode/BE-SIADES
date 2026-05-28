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
            'nik' => 'required|digits:16|unique:users,nik',
            'no_kk' => 'nullable|digits:16',
            'no_telp' => ['nullable', 'regex:/^08\d{8,11}$/'],
            'jenisKelamin' => 'required|in:L,P',
            'email' => 'nullable|email|unique:users,email|max:255',
            'rt' => 'nullable|string|max:10|regex:/^[A-Za-z0-9\s\-\/]+$/',
            'rw' => 'nullable|string|max:10|regex:/^[A-Za-z0-9\s\-\/]+$/',
            'alamat' => 'required|string|max:500',
            'tempatLahir' => 'nullable|string|max:100',
            'tanggalLahir' => 'nullable|date',

        ];
    }
}

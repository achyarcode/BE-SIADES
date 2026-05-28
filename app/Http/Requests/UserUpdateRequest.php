<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // 1. Antisipasi jika frontend mengirimkan dalam format snake_case (jenis_kelamin)
        if ($this->has('jenis_kelamin')) {
            $this->merge(['jenisKelamin' => $this->input('jenis_kelamin')]);
        }

        // 2. Map nomorKK (camelCase dari frontend) ke no_kk (snake_case untuk database)
        if ($this->has('nomorKK') && !$this->has('no_kk')) {
            $this->merge(['no_kk' => $this->input('nomorKK')]);
        }

        $nullableFields = [
            'no_kk',
            'nomorKK',
            'nomorWA',
            'email',
            'rt',
            'rw',
            'alamat',
            'tempatLahir',
            'tanggalLahir',
            'jenisKelamin',
            'mustUpdateCredentials',
        ];

        $normalized = [];

        foreach (array_merge(['namaLengkap', 'username', 'password', 'nik'], $nullableFields) as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));

                // Empty credential-like values should be treated as "not updating".
                if ($value === '' && in_array($field, ['username', 'password', 'nik'], true)) {
                    $this->request->remove($field);

                    continue;
                }

                // 2. PERBAIKAN LOGIKA: Ubah kata utuh menjadi inisial L/P untuk keperluan validator
                if ($field === 'jenisKelamin') {
                    $lowerValue = strtolower($value);
                    if ($lowerValue === 'laki-laki' || $lowerValue === 'laki - laki' || $lowerValue === 'l') {
                        $value = 'L';
                    } elseif ($lowerValue === 'perempuan' || $lowerValue === 'p') {
                        $value = 'P';
                    }
                }

                $normalized[$field] = $value === '' && in_array($field, $nullableFields, true) ? null : $value;
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
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) ? $routeUser->id : $routeUser;

        return [
            'namaLengkap' => 'sometimes|string|max:255',
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
            'password' => 'sometimes|string|min:6',
            'nik' => ['sometimes', 'string', 'size:16', Rule::unique('users')->ignore($userId)],
            'no_kk' => 'nullable|string|size:16',
            'nomorWA' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('users')->ignore($userId)],
            'rt' => 'nullable|string|max:10',
            'rw' => 'nullable|string|max:10',
            'alamat' => 'nullable|string',
            'jenisKelamin' => 'nullable|in:L,P', // Sekarang dijamin cocok karena input sudah dikonversi ke L/P
            'tempatLahir' => 'nullable|string|max:255',
            'tanggalLahir' => 'nullable|date',
            'mustUpdateCredentials' => 'nullable|boolean',
        ];
    }
}

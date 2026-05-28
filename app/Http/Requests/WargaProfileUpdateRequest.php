<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WargaProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Antisipasi jika frontend mengirim nama key alternatif untuk nomor WA dan Jenis Kelamin
        if ($this->has('jenis_kelamin')) {
            $this->merge(['jenisKelamin' => $this->input('jenis_kelamin')]);
        }

        if ($this->has('no_telp')) {
            $this->merge(['nomorWA' => $this->input('no_telp')]);
        } elseif ($this->has('no_wa')) {
            $this->merge(['nomorWA' => $this->input('no_wa')]);
        } elseif ($this->has('nomor_wa')) {
            $this->merge(['nomorWA' => $this->input('nomor_wa')]);
        }

        $normalized = [];
        foreach ([
            'namaLengkap',
            'username',
            'nomorkk',
            'alamat',
            'nomorWA',
            'email',
            'rt',
            'rw',
            'jenisKelamin',
            'tempatLahir',
            'tanggalLahir',
        ] as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);

                // PENTING: Paksa konversi ke string jika frontend mengirim format angka (integer)
                if (is_scalar($value)) {
                    $value = trim((string) $value);
                }

                // PROSES KONVERSI JENIS KELAMIN
                if ($field === 'jenisKelamin' && is_string($value)) {
                    $lowerValue = strtolower($value);
                    if ($lowerValue === 'laki-laki' || $lowerValue === 'laki - laki' || $lowerValue === 'l') {
                        $value = 'L';
                    } elseif ($lowerValue === 'perempuan' || $lowerValue === 'p') {
                        $value = 'P';
                    }
                }

                $normalized[$field] = $value;
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
            'nomorkk' => 'nullable|digits:16',
            'alamat' => 'nullable|string|max:500',
            'nomorWA' => ['nullable', 'regex:/^08\d{8,11}$/', Rule::unique('users', 'no_telp')->ignore($userId)],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'rt' => 'nullable|string|max:10|regex:/^[A-Za-z0-9\s\-\/]+$/',
            'rw' => 'nullable|string|max:10|regex:/^[A-Za-z0-9\s\-\/]+$/',
            'jenisKelamin' => 'required|in:L,P',
            'tempatLahir' => 'nullable|string|max:255',
            'tanggalLahir' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'nomorWA.unique' => 'Nomor HP sudah digunakan oleh akun lain.',
            'nomorWA.regex' => 'Nomor HP harus diawali 08 dan terdiri dari 10-13 digit.',
        ];
    }
}

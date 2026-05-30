<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuratStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_surat_id' => [
                'required_without:jenis_surat',
                'integer',
                Rule::exists('jenis_surats', 'id')->where('is_active', true),
            ],
            'jenis_surat' => [
                'required_without:jenis_surat_id',
                'string',
                'max:255',
                Rule::exists('jenis_surats', 'nama')->where('is_active', true),
            ],
            'keperluan' => 'nullable|string|max:1000',
            // Strict PDF-only validation + tighter size limit (1.5 MB) for storage efficiency.
            'file' => 'required|file|mimes:pdf|mimetypes:application/pdf,application/x-pdf|max:1536',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_surat_id.exists' => 'Jenis surat tidak tersedia atau sudah dinonaktifkan.',
            'jenis_surat.exists' => 'Jenis surat tidak tersedia atau sudah dinonaktifkan.',
            'jenis_surat_id.required_without' => 'Jenis surat wajib dipilih.',
            'jenis_surat.required_without' => 'Jenis surat wajib dipilih.',
        ];
    }
}

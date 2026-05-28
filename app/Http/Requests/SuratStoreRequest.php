<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuratStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_surat_id' => 'nullable|integer|exists:jenis_surats,id',
            'jenis_surat' => 'required_without:jenis_surat_id|string|max:255',
            'keperluan' => 'nullable|string|max:1000',
            // Strict PDF-only validation + tighter size limit (1.5 MB) for storage efficiency.
            'file' => 'required|file|mimes:pdf|mimetypes:application/pdf,application/x-pdf|max:1536',
        ];
    }
}

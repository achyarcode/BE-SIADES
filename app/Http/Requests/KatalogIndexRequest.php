<?php

namespace App\Http\Requests;

use App\Models\Katalog;
use Illuminate\Foundation\Http\FormRequest;

class KatalogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:'.implode(',', Katalog::statuses()),
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}

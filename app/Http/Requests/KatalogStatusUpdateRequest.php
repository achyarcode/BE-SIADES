<?php

namespace App\Http\Requests;

use App\Models\Katalog;
use Illuminate\Foundation\Http\FormRequest;

class KatalogStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:'.implode(',', Katalog::statuses()),
        ];
    }
}

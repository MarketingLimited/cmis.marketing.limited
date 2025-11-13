<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'post_text' => ['sometimes', 'string', 'max:5000'],
            'media_urls' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,scheduled,published,failed'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

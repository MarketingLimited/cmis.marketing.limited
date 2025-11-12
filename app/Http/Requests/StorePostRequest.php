<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['nullable', 'string', 'exists:cmis.campaigns,campaign_id'],
            'channel_id' => ['required', 'integer', 'exists:cmis.channels,channel_id'],
            'post_text' => ['required', 'string', 'max:5000'],
            'media_urls' => ['nullable', 'array'],
            'post_type' => ['required', 'string', 'in:text,image,video,carousel,story'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'org_id' => session('current_org_id'),
        ]);
    }
}

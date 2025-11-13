<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Core\Integration::class);
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'string', 'in:facebook,instagram,twitter,linkedin,google_ads'],
            'auth_type' => ['required', 'string', 'in:oauth2,api_key'],
            'credentials' => ['required', 'array'],
            'credentials.access_token' => ['required_if:auth_type,oauth2', 'string'],
            'credentials.api_key' => ['required_if:auth_type,api_key', 'string'],
            'status' => ['sometimes', 'string', 'in:active,inactive,expired'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'org_id' => session('current_org_id'),
            'connected_by' => auth()->id(),
        ]);
    }
}

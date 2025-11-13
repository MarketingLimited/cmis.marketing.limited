<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $integration = $this->route('integration');
        return $this->user()->can('update', $integration);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:active,inactive,expired'],
            'credentials' => ['sometimes', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}

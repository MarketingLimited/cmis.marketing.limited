<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContentItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'context_id' => ['nullable', 'string', 'exists:cmis.contexts_base,context_id'],
            'plan_id' => ['nullable', 'string', 'exists:cmis.content_plans,plan_id'],
            'item_type' => ['sometimes', 'string', 'in:post,article,video,image,story,reel'],
            'title' => ['sometimes', 'string', 'max:500'],
            'body' => ['sometimes', 'string'],
            'channel_id' => ['sometimes', 'integer', 'exists:cmis.channels,channel_id'],
            'scheduled_for' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,pending_approval,approved,scheduled,published,rejected'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'item_type.in' => 'Invalid content type selected.',
        ];
    }
}

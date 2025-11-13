<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to create content items
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
            'item_type' => ['required', 'string', 'in:post,article,video,image,story,reel'],
            'title' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'channel_id' => ['required', 'integer', 'exists:cmis.channels,channel_id'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
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
            'item_type.required' => 'Please select a content type.',
            'item_type.in' => 'Invalid content type selected.',
            'title.required' => 'The content title is required.',
            'body.required' => 'The content body is required.',
            'channel_id.required' => 'Please select a channel.',
            'scheduled_for.after' => 'The scheduled date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'org_id' => session('current_org_id'),
            'created_by' => auth()->id(),
        ]);
    }
}

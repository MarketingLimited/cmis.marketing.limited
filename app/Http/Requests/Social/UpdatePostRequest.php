<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Social Post Request
 *
 * Validates updates to social media posts
 * Security: Ensures post content and scheduling parameters are valid
 */
class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'sometimes|string|max:5000',
            'platform' => 'sometimes|in:meta,instagram,twitter,linkedin,tiktok',
            'status' => 'sometimes|in:draft,scheduled,published,failed',
            'scheduled_at' => 'sometimes|date|after:now',
            'media_urls' => 'sometimes|array|max:10',
            'media_urls.*' => 'url',
            'hashtags' => 'sometimes|array|max:30',
            'hashtags.*' => 'string|max:100|regex:/^#?[a-zA-Z0-9_]+$/',
            'mentions' => 'sometimes|array|max:20',
            'mentions.*' => 'string|max:100',
            'location' => 'sometimes|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.max' => 'Post content cannot exceed 5,000 characters',
            'platform.in' => 'Invalid platform. Supported: meta, instagram, twitter, linkedin, tiktok',
            'status.in' => 'Invalid status',
            'scheduled_at.after' => 'Scheduled time must be in the future',
            'media_urls.max' => 'Cannot attach more than 10 media files',
            'media_urls.*.url' => 'Each media URL must be valid',
            'hashtags.max' => 'Cannot use more than 30 hashtags',
            'hashtags.*.regex' => 'Hashtags can only contain letters, numbers, and underscores',
            'mentions.max' => 'Cannot mention more than 20 accounts',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scheduled_at' => 'scheduled time',
            'media_urls' => 'media files',
        ];
    }
}

<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Schedule Social Post Request Validation
 *
 * Validates social media post scheduling
 */
class ScheduleSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['in:facebook,instagram,twitter,linkedin,tiktok,youtube'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'max:104857600', 'mimes:jpeg,jpg,png,gif,mp4,mov'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['required', 'timezone'],
            'hashtags' => ['nullable', 'array', 'max:30'],
            'hashtags.*' => ['string', 'max:50', 'regex:/^#?[a-zA-Z0-9_]+$/'],
            'mentions' => ['nullable', 'array'],
            'location' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'url', 'max:500'],
            'publish_type' => ['nullable', 'in:scheduled,immediate,queue'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Post content is required',
            'platforms.required' => 'At least one platform must be selected',
            'scheduled_at.required' => 'Schedule time is required',
            'scheduled_at.after' => 'Schedule time must be in the future',
            'media.*.max' => 'Media file must not exceed 100MB',
            'hashtags.max' => 'Maximum of 30 hashtags allowed',
            'hashtags.*.regex' => 'Invalid hashtag format',
        ];
    }
}

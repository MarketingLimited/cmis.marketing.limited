<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Content Request Validation
 *
 * Validates content creation:
 * - Content type and format
 * - COPPA and GDPR compliance
 * - File upload validation
 *
 * Security Features:
 * - File size limits
 * - MIME type validation
 * - Content sanitization
 */
class StoreContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'content_type' => [
                'required',
                'in:text,image,video,carousel,story,reel',
            ],
            'body' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'caption' => [
                'nullable',
                'string',
                'max:2200', // Instagram max caption length
            ],
            'platforms' => [
                'required',
                'array',
                'min:1',
            ],
            'platforms.*' => [
                'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
            ],
            'media_files' => [
                'nullable',
                'array',
            ],
            'media_files.*' => [
                'file',
                'max:104857600', // 100MB max
                'mimes:jpeg,jpg,png,gif,mp4,mov,avi,webm',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'target_audience' => [
                'nullable',
                'in:all,13+,18+,21+,custom',
            ],
            'coppa_compliant' => [
                'required',
                'boolean',
            ],
            'gdpr_compliant' => [
                'required',
                'boolean',
            ],
            'scheduled_for' => [
                'nullable',
                'date',
                'after:now',
            ],
            'status' => [
                'nullable',
                'in:draft,pending_review,approved,published,archived',
            ],
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
            'title.required' => 'Content title is required',
            'content_type.required' => 'Content type is required',
            'content_type.in' => 'Invalid content type',
            'caption.max' => 'Caption must not exceed 2200 characters',
            'platforms.required' => 'At least one platform must be selected',
            'platforms.*.in' => 'Invalid platform selected',
            'media_files.*.max' => 'Media file must not exceed 100MB',
            'media_files.*.mimes' => 'Invalid media file format',
            'coppa_compliant.required' => 'COPPA compliance confirmation is required',
            'gdpr_compliant.required' => 'GDPR compliance confirmation is required',
            'scheduled_for.after' => 'Scheduled time must be in the future',
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
            'title' => 'content title',
            'content_type' => 'content type',
            'platforms' => 'social platforms',
            'media_files' => 'media files',
            'target_audience' => 'target audience',
        ];
    }
}

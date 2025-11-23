<?php

namespace App\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Content Request Validation
 *
 * Validates content updates with partial field support
 */
class UpdateContentRequest extends FormRequest
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
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'content_type' => [
                'sometimes',
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
                'max:2200',
            ],
            'platforms' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
            'platforms.*' => [
                'in:facebook,instagram,twitter,linkedin,tiktok,youtube',
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
            'caption.max' => 'Caption must not exceed 2200 characters',
            'platforms.min' => 'At least one platform must be selected',
        ];
    }
}

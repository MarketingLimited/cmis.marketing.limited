<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Video Asset Request
 *
 * Validates video asset updates
 * Security: Ensures modifications are within acceptable limits
 */
class UpdateVideoAssetRequest extends FormRequest
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
            'file' => 'sometimes|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/webm|max:524288',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'thumbnail' => 'sometimes|file|mimes:jpeg,jpg,png|max:2048',
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
            'file.mimetypes' => 'Video must be one of: MP4, MPEG, QuickTime, WebM',
            'file.max' => 'Video size cannot exceed 512MB',
            'name.max' => 'Asset name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'thumbnail.mimes' => 'Thumbnail must be JPEG or PNG',
            'thumbnail.max' => 'Thumbnail size cannot exceed 2MB',
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
            'thumbnail' => 'thumbnail image',
        ];
    }
}

<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Video Asset Request
 *
 * Validates video asset uploads
 * Security: Ensures file type, size, and duration are within acceptable limits
 */
class StoreVideoAssetRequest extends FormRequest
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
            'file' => 'required|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/webm|max:524288',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'thumbnail' => 'nullable|file|mimes:jpeg,jpg,png|max:2048',
            'duration_seconds' => 'sometimes|integer|min:1|max:3600',
            'width' => 'sometimes|integer|min:1|max:7680',
            'height' => 'sometimes|integer|min:1|max:4320',
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
            'file.required' => 'Video file is required',
            'file.mimetypes' => 'Video must be one of: MP4, MPEG, QuickTime, WebM',
            'file.max' => 'Video size cannot exceed 512MB',
            'name.required' => 'Asset name is required',
            'name.max' => 'Asset name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'thumbnail.mimes' => 'Thumbnail must be JPEG or PNG',
            'thumbnail.max' => 'Thumbnail size cannot exceed 2MB',
            'duration_seconds.min' => 'Video duration must be at least 1 second',
            'duration_seconds.max' => 'Video duration cannot exceed 1 hour (3,600 seconds)',
            'width.max' => 'Video width cannot exceed 7,680 pixels (8K)',
            'height.max' => 'Video height cannot exceed 4,320 pixels (8K)',
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
            'duration_seconds' => 'duration',
        ];
    }
}

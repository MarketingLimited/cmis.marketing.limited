<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Image Asset Request
 *
 * Validates image asset updates
 * Security: Ensures modifications are within acceptable limits
 */
class UpdateImageAssetRequest extends FormRequest
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
            'file' => 'sometimes|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
            'alt_text' => 'sometimes|string|max:500',
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
            'file.mimes' => 'Image must be one of: JPEG, PNG, GIF, WebP',
            'file.max' => 'Image size cannot exceed 10MB',
            'name.max' => 'Asset name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'alt_text.max' => 'Alt text cannot exceed 500 characters',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
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
            'alt_text' => 'alternative text',
        ];
    }
}

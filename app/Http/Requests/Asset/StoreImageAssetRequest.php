<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Image Asset Request
 *
 * Validates image asset uploads
 * Security: Ensures file type, size, and dimensions are within acceptable limits
 */
class StoreImageAssetRequest extends FormRequest
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
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,webp|max:10240',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'alt_text' => 'nullable|string|max:500',
            'width' => 'sometimes|integer|min:1|max:10000',
            'height' => 'sometimes|integer|min:1|max:10000',
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
            'file.required' => 'Image file is required',
            'file.mimes' => 'Image must be one of: JPEG, PNG, GIF, WebP',
            'file.max' => 'Image size cannot exceed 10MB',
            'name.required' => 'Asset name is required',
            'name.max' => 'Asset name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'alt_text.max' => 'Alt text cannot exceed 500 characters',
            'tags.*.max' => 'Each tag cannot exceed 50 characters',
            'width.max' => 'Image width cannot exceed 10,000 pixels',
            'height.max' => 'Image height cannot exceed 10,000 pixels',
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

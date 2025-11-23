<?php

namespace App\Http\Requests\ContentLibrary;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Content Library Item Request Validation
 *
 * Validates content library asset storage
 */
class StoreLibraryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:image,video,document,template,brand_asset'],
            'category' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:524288'], // 512MB max
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Asset name is required',
            'type.required' => 'Asset type is required',
            'file.required' => 'File upload is required',
            'file.max' => 'File size must not exceed 512MB',
            'tags.max' => 'Maximum of 20 tags allowed',
        ];
    }
}

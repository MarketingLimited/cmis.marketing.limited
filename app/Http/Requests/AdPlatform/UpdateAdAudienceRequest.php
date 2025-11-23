<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ad Audience Request
 *
 * Validates updates to existing custom audiences
 * Security: Ensures audience modifications are valid
 */
class UpdateAdAudienceRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'targeting_spec' => 'sometimes|array',
            'size_estimate' => 'sometimes|integer|min:0',
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
            'name.max' => 'Audience name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'size_estimate.min' => 'Size estimate must be a positive number',
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
            'targeting_spec' => 'targeting specification',
            'size_estimate' => 'size estimate',
        ];
    }
}

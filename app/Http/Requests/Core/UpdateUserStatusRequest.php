<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update User Status Request Validation
 *
 * Validates user activation/deactivation:
 * - Boolean status flag
 *
 * Security Features:
 * - Prevents self-deactivation (should be enforced at controller level)
 * - Simple boolean validation
 */
class UpdateUserStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware/policies
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
            'is_active' => [
                'required',
                'boolean',
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
            'is_active.required' => 'Status is required',
            'is_active.boolean' => 'Status must be true or false',
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
            'is_active' => 'user status',
        ];
    }
}

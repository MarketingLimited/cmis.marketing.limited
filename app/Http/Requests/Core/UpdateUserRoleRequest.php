<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update User Role Request Validation
 *
 * Validates user role updates within an organization:
 * - Role ID must exist in the database
 * - UUID format validation
 *
 * Security Features:
 * - Role existence validation prevents invalid role assignment
 * - Proper authorization should be enforced at controller/policy level
 */
class UpdateUserRoleRequest extends FormRequest
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
            'role_id' => [
                'required',
                'uuid',
                'exists:cmis.roles,role_id',
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
            'role_id.required' => 'Role is required',
            'role_id.uuid' => 'Invalid role ID format',
            'role_id.exists' => 'The selected role does not exist',
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
            'role_id' => 'role',
        ];
    }
}

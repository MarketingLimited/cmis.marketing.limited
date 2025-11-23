<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Invite User Request Validation
 *
 * Validates user invitation to organization:
 * - Valid email format
 * - Role existence validation
 * - Optional custom message
 *
 * Security Features:
 * - Role ID must exist in database
 * - Message length limited to prevent abuse
 * - Email validation
 */
class InviteUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization should be handled by middleware/policies
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
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'role_id' => [
                'required',
                'uuid',
                'exists:cmis.roles,role_id',
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
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
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address must not exceed 255 characters',
            'role_id.required' => 'Role is required',
            'role_id.uuid' => 'Invalid role format',
            'role_id.exists' => 'The selected role does not exist',
            'message.max' => 'Custom message must not exceed 1000 characters',
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
            'email' => 'email address',
            'role_id' => 'role',
            'message' => 'custom message',
        ];
    }
}

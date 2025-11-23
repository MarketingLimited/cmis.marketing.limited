<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Invite Team Member Request Validation
 *
 * Validates team member invitation:
 * - Email validation
 * - Role-based access control
 * - Account access assignments
 *
 * Security Features:
 * - Valid role selection
 * - UUID validation for account IDs
 * - Message length limits
 */
class InviteTeamMemberRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'role' => [
                'required',
                'in:owner,admin,manager,editor,contributor,viewer',
            ],
            'message' => [
                'nullable',
                'string',
                'max:500',
            ],
            'account_access' => [
                'nullable',
                'array',
            ],
            'account_access.*' => [
                'uuid',
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
            'role.required' => 'Role is required',
            'role.in' => 'Invalid role selected. Must be one of: owner, admin, manager, editor, contributor, viewer',
            'message.max' => 'Message must not exceed 500 characters',
            'account_access.array' => 'Account access must be an array',
            'account_access.*.uuid' => 'Invalid account ID format',
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
            'role' => 'team role',
            'message' => 'welcome message',
            'account_access' => 'account access',
        ];
    }
}

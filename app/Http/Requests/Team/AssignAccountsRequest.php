<?php

namespace App\Http\Requests\Team;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Assign Accounts Request Validation
 *
 * Validates account assignments for team members:
 * - Array of UUID account IDs
 *
 * Security Features:
 * - UUID format validation
 * - Array validation to prevent malformed input
 */
class AssignAccountsRequest extends FormRequest
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
            'account_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'account_ids.*' => [
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
            'account_ids.required' => 'At least one account must be selected',
            'account_ids.array' => 'Account IDs must be provided as an array',
            'account_ids.min' => 'At least one account must be selected',
            'account_ids.*.uuid' => 'Invalid account ID format',
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
            'account_ids' => 'account selection',
        ];
    }
}

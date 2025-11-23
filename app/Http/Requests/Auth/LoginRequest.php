<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Login Request Validation
 *
 * Validates user login credentials with security measures:
 * - Email format validation
 * - Required password field
 * - Optional remember me flag
 *
 * Security Features:
 * - Rate limiting should be applied at route level
 * - Password is validated but not exposed in logs
 */
class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Login is always allowed (authentication happens after validation)
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
            'password' => [
                'required',
                'string',
            ],
            'remember' => [
                'nullable',
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
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address must not exceed 255 characters',
            'password.required' => 'Password is required',
            'remember.boolean' => 'Remember me must be true or false',
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
            'password' => 'password',
            'remember' => 'remember me option',
        ];
    }
}

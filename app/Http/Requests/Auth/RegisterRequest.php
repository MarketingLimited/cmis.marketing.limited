<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * User Registration Request Validation
 *
 * Validates new user registration with security best practices:
 * - Strong password requirements
 * - Email uniqueness validation
 * - Name field validation
 *
 * Security Features:
 * - Password complexity requirements (Laravel default)
 * - Unique email check
 * - Input sanitization
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Registration is publicly accessible
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
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:cmis.users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
            'org_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'terms_accepted' => [
                'required',
                'accepted',
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
            'name.required' => 'Please provide your full name',
            'name.min' => 'Name must be at least 2 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'password_confirmation.required' => 'Please confirm your password',
            'terms_accepted.required' => 'You must accept the terms and conditions',
            'terms_accepted.accepted' => 'You must accept the terms and conditions',
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
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'org_name' => 'organization name',
            'terms_accepted' => 'terms and conditions',
        ];
    }
}

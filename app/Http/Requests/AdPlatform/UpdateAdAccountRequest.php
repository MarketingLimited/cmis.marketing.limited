<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ad Account Request
 *
 * Validates updates to existing ad accounts
 * Security: Ensures account modifications are valid
 */
class UpdateAdAccountRequest extends FormRequest
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
            'account_name' => 'sometimes|string|max:255',
            'currency' => 'sometimes|string|size:3',
            'timezone' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:active,inactive,paused',
            'credentials' => 'sometimes|array',
            'credentials.access_token' => 'required_with:credentials|string',
            'credentials.refresh_token' => 'sometimes|string',
            'credentials.expires_at' => 'sometimes|date',
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
            'account_name.max' => 'Account name cannot exceed 255 characters',
            'currency.size' => 'Currency code must be exactly 3 characters',
            'status.in' => 'Invalid status. Supported: active, inactive, paused',
            'credentials.access_token.required_with' => 'Access token is required when credentials are provided',
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
            'account_name' => 'account name',
            'credentials.access_token' => 'access token',
            'credentials.refresh_token' => 'refresh token',
        ];
    }
}

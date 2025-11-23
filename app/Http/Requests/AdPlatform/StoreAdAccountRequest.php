<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ad Account Request
 *
 * Validates creation of new ad accounts
 * Security: Ensures platform credentials and account data are valid
 */
class StoreAdAccountRequest extends FormRequest
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
            'platform' => 'required|string|in:meta,google,tiktok,linkedin,twitter,snapchat',
            'account_name' => 'required|string|max:255',
            'account_id' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
            'timezone' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive,paused',
            'credentials' => 'nullable|array',
            'credentials.access_token' => 'required_with:credentials|string',
            'credentials.refresh_token' => 'nullable|string',
            'credentials.expires_at' => 'nullable|date',
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
            'platform.required' => 'Platform is required',
            'platform.in' => 'Invalid platform. Supported: meta, google, tiktok, linkedin, twitter, snapchat',
            'account_name.required' => 'Account name is required',
            'account_name.max' => 'Account name cannot exceed 255 characters',
            'account_id.required' => 'Platform account ID is required',
            'currency.required' => 'Currency is required',
            'currency.size' => 'Currency code must be exactly 3 characters',
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
            'account_id' => 'platform account ID',
            'credentials.access_token' => 'access token',
            'credentials.refresh_token' => 'refresh token',
        ];
    }
}

<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Integration Settings Request
 *
 * Validates platform integration configuration updates
 * Security: Ensures credentials and settings are properly validated
 */
class UpdateIntegrationSettingsRequest extends FormRequest
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
            'status' => 'sometimes|in:active,inactive,error,pending',
            'settings' => 'sometimes|array',
            'settings.auto_sync' => 'sometimes|boolean',
            'settings.sync_frequency' => 'sometimes|in:hourly,daily,weekly,manual',
            'settings.webhook_enabled' => 'sometimes|boolean',
            'settings.webhook_url' => 'sometimes|url|max:500',
            'settings.notifications_enabled' => 'sometimes|boolean',
            'credentials' => 'sometimes|array',
            'credentials.access_token' => 'sometimes|string',
            'credentials.refresh_token' => 'sometimes|string',
            'credentials.expires_at' => 'sometimes|date',
            'credentials.scopes' => 'sometimes|array',
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
            'status.in' => 'Invalid status. Supported: active, inactive, error, pending',
            'settings.sync_frequency.in' => 'Invalid sync frequency. Supported: hourly, daily, weekly, manual',
            'settings.webhook_url.url' => 'Webhook URL must be a valid URL',
            'settings.webhook_url.max' => 'Webhook URL cannot exceed 500 characters',
            'credentials.expires_at.date' => 'Token expiration must be a valid date',
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
            'settings.sync_frequency' => 'sync frequency',
            'settings.webhook_url' => 'webhook URL',
            'credentials.access_token' => 'access token',
            'credentials.refresh_token' => 'refresh token',
            'credentials.expires_at' => 'token expiration',
        ];
    }
}

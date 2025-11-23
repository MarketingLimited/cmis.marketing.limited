<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Settings Request Validation
 *
 * Validates organization/user settings updates:
 * - Notification preferences
 * - Display settings
 * - Integration settings
 *
 * Security Features:
 * - Valid timezone selection
 * - Email validation
 * - Boolean validation for flags
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'timezone' => [
                'nullable',
                'timezone',
            ],
            'currency' => [
                'nullable',
                'string',
                'size:3', // ISO 4217 currency codes
                'regex:/^[A-Z]{3}$/',
            ],
            'language' => [
                'nullable',
                'string',
                'size:2', // ISO 639-1 language codes
            ],
            'date_format' => [
                'nullable',
                'in:Y-m-d,d/m/Y,m/d/Y,d.m.Y',
            ],
            'time_format' => [
                'nullable',
                'in:24h,12h',
            ],
            'notifications' => [
                'nullable',
                'array',
            ],
            'notifications.email_enabled' => [
                'nullable',
                'boolean',
            ],
            'notifications.sms_enabled' => [
                'nullable',
                'boolean',
            ],
            'notifications.push_enabled' => [
                'nullable',
                'boolean',
            ],
            'notifications.digest_frequency' => [
                'nullable',
                'in:realtime,hourly,daily,weekly,never',
            ],
            'notifications.alert_types' => [
                'nullable',
                'array',
            ],
            'notifications.alert_types.*' => [
                'in:campaign_status,budget_alerts,performance_alerts,approval_requests,team_invites',
            ],
            'display_preferences' => [
                'nullable',
                'array',
            ],
            'display_preferences.theme' => [
                'nullable',
                'in:light,dark,auto',
            ],
            'display_preferences.compact_mode' => [
                'nullable',
                'boolean',
            ],
            'display_preferences.sidebar_collapsed' => [
                'nullable',
                'boolean',
            ],
            'api_settings' => [
                'nullable',
                'array',
            ],
            'api_settings.rate_limit' => [
                'nullable',
                'integer',
                'min:10',
                'max:10000',
            ],
            'api_settings.webhook_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'security' => [
                'nullable',
                'array',
            ],
            'security.two_factor_enabled' => [
                'nullable',
                'boolean',
            ],
            'security.session_timeout_minutes' => [
                'nullable',
                'integer',
                'min:5',
                'max:1440', // Max 24 hours
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
            'timezone.timezone' => 'Invalid timezone selected',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217)',
            'currency.regex' => 'Currency code must be uppercase letters',
            'language.size' => 'Language code must be 2 characters (ISO 639-1)',
            'date_format.in' => 'Invalid date format selected',
            'time_format.in' => 'Invalid time format. Must be 12h or 24h',
            'notifications.digest_frequency.in' => 'Invalid digest frequency',
            'api_settings.rate_limit.min' => 'Rate limit must be at least 10 requests',
            'api_settings.rate_limit.max' => 'Rate limit cannot exceed 10,000 requests',
            'api_settings.webhook_url.url' => 'Webhook URL must be a valid URL',
            'security.session_timeout_minutes.min' => 'Session timeout must be at least 5 minutes',
            'security.session_timeout_minutes.max' => 'Session timeout cannot exceed 24 hours',
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
            'timezone' => 'time zone',
            'currency' => 'currency code',
            'language' => 'language code',
            'notifications.digest_frequency' => 'digest frequency',
            'api_settings.rate_limit' => 'API rate limit',
            'api_settings.webhook_url' => 'webhook URL',
            'security.session_timeout_minutes' => 'session timeout',
        ];
    }
}

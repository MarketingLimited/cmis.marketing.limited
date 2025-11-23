<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Alert Rule Request
 *
 * Validates updates to performance alert rules
 * Security: Ensures alert modifications are valid
 */
class UpdateAlertRuleRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'metric' => 'sometimes|string|in:ctr,cpc,cpm,roas,spend,conversions,reach',
            'condition' => 'sometimes|in:greater_than,less_than,equals,greater_or_equal,less_or_equal',
            'threshold' => 'sometimes|numeric',
            'timeframe_minutes' => 'sometimes|integer|min:5|max:10080',
            'notification_channels' => 'sometimes|array|min:1',
            'notification_channels.*' => 'in:email,slack,webhook',
            'recipients' => 'sometimes|array',
            'recipients.*' => 'email',
            'webhook_url' => 'sometimes|url',
            'slack_channel' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'cooldown_minutes' => 'sometimes|integer|min:5|max:1440',
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
            'name.max' => 'Alert name cannot exceed 255 characters',
            'metric.in' => 'Invalid metric. Supported: ctr, cpc, cpm, roas, spend, conversions, reach',
            'condition.in' => 'Invalid condition',
            'threshold.numeric' => 'Threshold must be a number',
            'timeframe_minutes.min' => 'Timeframe must be at least 5 minutes',
            'timeframe_minutes.max' => 'Timeframe cannot exceed 7 days (10,080 minutes)',
            'notification_channels.min' => 'At least one notification channel is required',
            'notification_channels.*.in' => 'Invalid notification channel',
            'recipients.*.email' => 'Each recipient must be a valid email address',
            'webhook_url.url' => 'Webhook URL must be a valid URL',
            'cooldown_minutes.min' => 'Cooldown must be at least 5 minutes',
            'cooldown_minutes.max' => 'Cooldown cannot exceed 24 hours (1,440 minutes)',
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
            'timeframe_minutes' => 'timeframe',
            'notification_channels' => 'notification channels',
            'webhook_url' => 'webhook URL',
            'slack_channel' => 'Slack channel',
            'cooldown_minutes' => 'cooldown period',
        ];
    }
}

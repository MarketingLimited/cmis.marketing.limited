<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Alert Rule Request
 *
 * Validates creation of performance alert rules
 * Security: Ensures alert thresholds and configurations are valid
 */
class StoreAlertRuleRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'metric' => 'required|string|in:ctr,cpc,cpm,roas,spend,conversions,reach',
            'condition' => 'required|in:greater_than,less_than,equals,greater_or_equal,less_or_equal',
            'threshold' => 'required|numeric',
            'timeframe_minutes' => 'required|integer|min:5|max:10080',
            'notification_channels' => 'required|array|min:1',
            'notification_channels.*' => 'in:email,slack,webhook',
            'recipients' => 'required_if:notification_channels.*,email|array',
            'recipients.*' => 'email',
            'webhook_url' => 'required_if:notification_channels.*,webhook|url',
            'slack_channel' => 'required_if:notification_channels.*,slack|string',
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
            'name.required' => 'Alert name is required',
            'name.max' => 'Alert name cannot exceed 255 characters',
            'metric.required' => 'Metric to monitor is required',
            'metric.in' => 'Invalid metric. Supported: ctr, cpc, cpm, roas, spend, conversions, reach',
            'condition.required' => 'Condition is required',
            'condition.in' => 'Invalid condition',
            'threshold.required' => 'Threshold value is required',
            'threshold.numeric' => 'Threshold must be a number',
            'timeframe_minutes.required' => 'Timeframe is required',
            'timeframe_minutes.min' => 'Timeframe must be at least 5 minutes',
            'timeframe_minutes.max' => 'Timeframe cannot exceed 7 days (10,080 minutes)',
            'notification_channels.required' => 'At least one notification channel is required',
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

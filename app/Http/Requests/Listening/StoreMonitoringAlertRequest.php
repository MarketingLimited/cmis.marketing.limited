<?php

namespace App\Http\Requests\Listening;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Monitoring Alert Request Validation
 *
 * Validates alert configuration for social listening:
 * - Alert conditions and thresholds
 * - Notification settings
 * - Recipient configuration
 *
 * Security Features:
 * - Email validation for recipients
 * - Threshold limits to prevent abuse
 * - Valid metric selection
 */
class StoreMonitoringAlertRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'metric_type' => [
                'required',
                'in:mentions,sentiment,engagement,reach,volume,keyword_match',
            ],
            'condition' => [
                'required',
                'in:greater_than,less_than,equals,changes_by',
            ],
            'threshold_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'threshold_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'time_window' => [
                'nullable',
                'in:1h,6h,12h,24h,7d,30d',
            ],
            'notification_channels' => [
                'required',
                'array',
                'min:1',
            ],
            'notification_channels.*' => [
                'in:email,sms,slack,webhook',
            ],
            'recipients' => [
                'required_if:notification_channels.*,email',
                'array',
            ],
            'recipients.*' => [
                'email',
            ],
            'is_active' => [
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
            'name.required' => 'Alert name is required',
            'metric_type.required' => 'Metric type is required',
            'metric_type.in' => 'Invalid metric type selected',
            'condition.required' => 'Alert condition is required',
            'condition.in' => 'Invalid condition selected',
            'threshold_value.required' => 'Threshold value is required',
            'threshold_value.min' => 'Threshold value must be 0 or greater',
            'threshold_percentage.max' => 'Threshold percentage cannot exceed 100%',
            'notification_channels.required' => 'At least one notification channel must be selected',
            'notification_channels.*.in' => 'Invalid notification channel',
            'recipients.required_if' => 'Email recipients are required when email notifications are enabled',
            'recipients.*.email' => 'Invalid email address format',
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
            'name' => 'alert name',
            'metric_type' => 'metric type',
            'condition' => 'alert condition',
            'threshold_value' => 'threshold value',
            'notification_channels' => 'notification channels',
            'recipients' => 'email recipients',
        ];
    }
}

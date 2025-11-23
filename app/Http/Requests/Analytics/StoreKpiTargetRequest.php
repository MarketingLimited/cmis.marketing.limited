<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store KPI Target Request Validation
 *
 * Validates KPI target configuration:
 * - Metric selection and target values
 * - Time period and comparison settings
 * - Alert thresholds
 *
 * Security Features:
 * - Valid metric types
 * - Numeric validation for targets
 * - Date range validation
 */
class StoreKpiTargetRequest extends FormRequest
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
            'metric_type' => [
                'required',
                'in:impressions,clicks,conversions,ctr,cpc,cpa,roas,engagement_rate,reach,frequency',
            ],
            'target_value' => [
                'required',
                'numeric',
                'min:0',
            ],
            'comparison_operator' => [
                'required',
                'in:greater_than,less_than,equals,greater_than_or_equal,less_than_or_equal',
            ],
            'time_period' => [
                'required',
                'in:daily,weekly,monthly,quarterly,yearly,custom',
            ],
            'start_date' => [
                'required_if:time_period,custom',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'required_if:time_period,custom',
                'date',
                'after_or_equal:start_date',
            ],
            'campaign_id' => [
                'nullable',
                'uuid',
                'exists:cmis.campaigns,campaign_id',
            ],
            'alert_enabled' => [
                'nullable',
                'boolean',
            ],
            'alert_threshold_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
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
            'name.required' => 'KPI target name is required',
            'metric_type.required' => 'Metric type is required',
            'metric_type.in' => 'Invalid metric type selected',
            'target_value.required' => 'Target value is required',
            'target_value.min' => 'Target value must be 0 or greater',
            'comparison_operator.required' => 'Comparison operator is required',
            'time_period.required' => 'Time period is required',
            'start_date.required_if' => 'Start date is required for custom time periods',
            'end_date.required_if' => 'End date is required for custom time periods',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'campaign_id.exists' => 'The selected campaign does not exist',
            'alert_threshold_percentage.max' => 'Alert threshold cannot exceed 100%',
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
            'name' => 'KPI name',
            'metric_type' => 'metric type',
            'target_value' => 'target value',
            'comparison_operator' => 'comparison operator',
            'time_period' => 'time period',
            'campaign_id' => 'campaign',
        ];
    }
}

<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update KPI Target Request Validation
 *
 * Validates updates to existing KPI targets
 */
class UpdateKpiTargetRequest extends FormRequest
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
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'metric_type' => [
                'sometimes',
                'required',
                'in:impressions,clicks,conversions,ctr,cpc,cpa,roas,engagement_rate,reach,frequency',
            ],
            'target_value' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'comparison_operator' => [
                'sometimes',
                'required',
                'in:greater_than,less_than,equals,greater_than_or_equal,less_than_or_equal',
            ],
            'time_period' => [
                'sometimes',
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
            'metric_type.in' => 'Invalid metric type selected',
            'target_value.min' => 'Target value must be 0 or greater',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
        ];
    }
}

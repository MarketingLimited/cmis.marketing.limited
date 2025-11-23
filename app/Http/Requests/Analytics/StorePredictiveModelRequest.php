<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Predictive Model Request Validation
 *
 * Validates predictive analytics model configuration:
 * - Model type and training parameters
 * - Data source selection
 * - Prediction targets
 *
 * Security Features:
 * - Valid model type selection
 * - Training data limits
 * - Target metric validation
 */
class StorePredictiveModelRequest extends FormRequest
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
            'model_type' => [
                'required',
                'in:conversion_prediction,budget_optimization,audience_targeting,performance_forecasting,churn_prediction',
            ],
            'target_metric' => [
                'required',
                'in:conversions,revenue,roas,ctr,engagement_rate,retention_rate',
            ],
            'training_data_source' => [
                'required',
                'in:historical_campaigns,similar_campaigns,industry_benchmarks,custom',
            ],
            'training_period_days' => [
                'required',
                'integer',
                'min:30',
                'max:730', // Max 2 years
            ],
            'prediction_horizon_days' => [
                'required',
                'integer',
                'min:1',
                'max:90', // Max 90 days forecast
            ],
            'campaign_ids' => [
                'nullable',
                'array',
            ],
            'campaign_ids.*' => [
                'uuid',
                'exists:cmis.campaigns,campaign_id',
            ],
            'features' => [
                'nullable',
                'array',
            ],
            'features.*' => [
                'string',
                'in:time_of_day,day_of_week,audience_demographics,budget,creative_type,platform,targeting_parameters',
            ],
            'auto_retrain' => [
                'nullable',
                'boolean',
            ],
            'retrain_frequency_days' => [
                'nullable',
                'integer',
                'min:7',
                'max:90',
            ],
            'confidence_threshold' => [
                'nullable',
                'numeric',
                'min:0.5',
                'max:0.99',
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
            'name.required' => 'Model name is required',
            'model_type.required' => 'Model type is required',
            'model_type.in' => 'Invalid model type selected',
            'target_metric.required' => 'Target metric is required',
            'target_metric.in' => 'Invalid target metric selected',
            'training_period_days.min' => 'Training period must be at least 30 days',
            'training_period_days.max' => 'Training period cannot exceed 2 years',
            'prediction_horizon_days.max' => 'Prediction horizon cannot exceed 90 days',
            'retrain_frequency_days.min' => 'Retrain frequency must be at least 7 days',
            'confidence_threshold.min' => 'Confidence threshold must be at least 0.5 (50%)',
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
            'name' => 'model name',
            'model_type' => 'model type',
            'target_metric' => 'target metric',
            'training_period_days' => 'training period',
            'prediction_horizon_days' => 'prediction horizon',
            'confidence_threshold' => 'confidence threshold',
        ];
    }
}

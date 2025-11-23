<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Experiment Request
 *
 * Validates creation of A/B tests and experiments
 * Security: Ensures experiment configurations are statistically valid
 */
class StoreExperimentRequest extends FormRequest
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
            'description' => 'nullable|string|max:1000',
            'hypothesis' => 'required|string|max:1000',
            'experiment_type' => 'required|in:ab_test,multivariate,split_url',
            'primary_metric' => 'required|string|in:ctr,cpc,conversions,roas,engagement',
            'secondary_metrics' => 'nullable|array',
            'secondary_metrics.*' => 'string|in:ctr,cpc,conversions,roas,engagement,reach,frequency',
            'confidence_level' => 'sometimes|numeric|min:0.8|max:0.99',
            'minimum_sample_size' => 'sometimes|integer|min:100|max:1000000',
            'traffic_allocation' => 'sometimes|numeric|min:0.01|max:1.00',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'variants' => 'required|array|min:2|max:10',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.description' => 'nullable|string|max:500',
            'variants.*.traffic_percent' => 'required|numeric|min:1|max:100',
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
            'name.required' => 'Experiment name is required',
            'name.max' => 'Experiment name cannot exceed 255 characters',
            'hypothesis.required' => 'Hypothesis is required',
            'hypothesis.max' => 'Hypothesis cannot exceed 1,000 characters',
            'experiment_type.required' => 'Experiment type is required',
            'experiment_type.in' => 'Invalid experiment type',
            'primary_metric.required' => 'Primary metric is required',
            'confidence_level.min' => 'Confidence level must be at least 0.8 (80%)',
            'confidence_level.max' => 'Confidence level cannot exceed 0.99 (99%)',
            'minimum_sample_size.min' => 'Minimum sample size must be at least 100',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
            'end_date.after' => 'End date must be after start date',
            'variants.required' => 'At least 2 variants are required',
            'variants.min' => 'At least 2 variants are required for an experiment',
            'variants.max' => 'Cannot exceed 10 variants',
            'variants.*.name.required' => 'Variant name is required',
            'variants.*.traffic_percent.required' => 'Traffic percentage is required for each variant',
            'variants.*.traffic_percent.min' => 'Traffic percentage must be at least 1%',
            'variants.*.traffic_percent.max' => 'Traffic percentage cannot exceed 100%',
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
            'experiment_type' => 'experiment type',
            'primary_metric' => 'primary metric',
            'secondary_metrics' => 'secondary metrics',
            'confidence_level' => 'confidence level',
            'minimum_sample_size' => 'minimum sample size',
            'traffic_allocation' => 'traffic allocation',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }

    /**
     * Validate total traffic allocation
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('variants')) {
                $totalPercent = collect($this->input('variants'))
                    ->sum('traffic_percent');

                if ($totalPercent != 100) {
                    $validator->errors()->add(
                        'variants',
                        'Total traffic allocation must equal 100% (currently: ' . $totalPercent . '%)'
                    );
                }
            }
        });
    }
}

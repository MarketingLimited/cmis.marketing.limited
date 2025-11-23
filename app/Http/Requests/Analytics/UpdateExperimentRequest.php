<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Experiment (A/B Test) Request Validation
 *
 * Validates A/B test experiment updates
 */
class UpdateExperimentRequest extends FormRequest
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
            'hypothesis' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => [
                'sometimes',
                'required',
                'in:draft,running,paused,completed,cancelled',
            ],
            'traffic_allocation' => [
                'nullable',
                'array',
            ],
            'traffic_allocation.control' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'traffic_allocation.variant_a' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'traffic_allocation.variant_b' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'success_metric' => [
                'sometimes',
                'required',
                'in:conversions,revenue,ctr,engagement_rate,roas',
            ],
            'minimum_sample_size' => [
                'nullable',
                'integer',
                'min:100',
            ],
            'confidence_level' => [
                'nullable',
                'numeric',
                'in:0.90,0.95,0.99',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:today',
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
            'status.in' => 'Invalid experiment status',
            'traffic_allocation.*.max' => 'Traffic allocation cannot exceed 100%',
            'success_metric.in' => 'Invalid success metric selected',
            'minimum_sample_size.min' => 'Minimum sample size must be at least 100',
            'confidence_level.in' => 'Confidence level must be 90%, 95%, or 99%',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Validate total traffic allocation equals 100% if provided
        if ($this->has('traffic_allocation')) {
            $allocation = $this->input('traffic_allocation');
            $total = ($allocation['control'] ?? 0) +
                     ($allocation['variant_a'] ?? 0) +
                     ($allocation['variant_b'] ?? 0);

            if ($total > 0 && abs($total - 100) > 0.01) {
                $this->merge([
                    'traffic_allocation_invalid' => true,
                ]);
            }
        }
    }
}

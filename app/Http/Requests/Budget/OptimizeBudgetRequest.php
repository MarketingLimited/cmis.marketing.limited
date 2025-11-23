<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Optimize Budget Allocation Request
 *
 * Validates budget optimization parameters
 * Security: Ensures optimization goals and budgets are valid
 */
class OptimizeBudgetRequest extends FormRequest
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
            'ad_account_id' => 'required|uuid|exists:cmis_platform.ad_accounts,ad_account_id',
            'total_budget' => [
                'required',
                'numeric',
                'min:1',
                'max:10000000',
            ],
            'goal' => 'nullable|in:roi,conversions,reach',
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
            'ad_account_id.required' => 'Ad account ID is required',
            'ad_account_id.uuid' => 'Ad account ID must be a valid UUID',
            'ad_account_id.exists' => 'Ad account not found',
            'total_budget.required' => 'Total budget is required',
            'total_budget.min' => 'Total budget must be at least 1',
            'total_budget.max' => 'Total budget cannot exceed 10,000,000',
            'goal.in' => 'Invalid optimization goal. Supported: roi, conversions, reach',
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
            'ad_account_id' => 'ad account',
            'total_budget' => 'total budget',
        ];
    }
}

<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Campaign Budget Request
 *
 * Validates campaign budget updates
 * Security: Ensures budgets are within acceptable ranges
 */
class UpdateCampaignBudgetRequest extends FormRequest
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
            'budget_type' => 'required|in:daily,lifetime',
            'daily_budget' => [
                'required_if:budget_type,daily',
                'nullable',
                'numeric',
                'min:1',
                'max:1000000',
            ],
            'lifetime_budget' => [
                'required_if:budget_type,lifetime',
                'nullable',
                'numeric',
                'min:1',
                'max:10000000',
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
            'budget_type.required' => 'Budget type is required',
            'budget_type.in' => 'Budget type must be either daily or lifetime',
            'daily_budget.required_if' => 'Daily budget is required when budget type is daily',
            'daily_budget.min' => 'Daily budget must be at least 1',
            'daily_budget.max' => 'Daily budget cannot exceed 1,000,000',
            'lifetime_budget.required_if' => 'Lifetime budget is required when budget type is lifetime',
            'lifetime_budget.min' => 'Lifetime budget must be at least 1',
            'lifetime_budget.max' => 'Lifetime budget cannot exceed 10,000,000',
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
            'budget_type' => 'budget type',
            'daily_budget' => 'daily budget',
            'lifetime_budget' => 'lifetime budget',
        ];
    }
}

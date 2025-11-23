<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Budget Request Validation
 *
 * Validates budget allocation updates
 */
class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget_type' => ['sometimes', 'required', 'in:daily,weekly,monthly,lifetime,campaign'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:1', 'max:999999999.99'],
            'currency' => ['sometimes', 'required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'allocation' => ['nullable', 'array'],
            'allocation.*.platform' => ['required_with:allocation', 'string'],
            'allocation.*.percentage' => ['required_with:allocation', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'auto_adjust' => ['nullable', 'boolean'],
            'pacing' => ['nullable', 'in:standard,accelerated'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Budget amount must be at least 1',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217)',
            'allocation.*.percentage.max' => 'Allocation percentage cannot exceed 100%',
        ];
    }
}

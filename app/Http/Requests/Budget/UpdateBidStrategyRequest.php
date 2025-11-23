<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Bid Strategy Request
 *
 * Validates bid strategy updates for campaigns
 * Security: Ensures bid amounts are within acceptable ranges
 */
class UpdateBidStrategyRequest extends FormRequest
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
            'bid_strategy' => 'required|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'bid_amount' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:10000',
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
            'bid_strategy.required' => 'Bid strategy is required',
            'bid_strategy.in' => 'Invalid bid strategy. Supported: lowest_cost, cost_cap, bid_cap, target_cost',
            'bid_amount.numeric' => 'Bid amount must be a number',
            'bid_amount.min' => 'Bid amount must be at least 0.01',
            'bid_amount.max' => 'Bid amount cannot exceed 10,000',
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
            'bid_strategy' => 'bid strategy',
            'bid_amount' => 'bid amount',
        ];
    }
}

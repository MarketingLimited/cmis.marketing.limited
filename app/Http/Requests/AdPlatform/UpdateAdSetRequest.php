<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ad Set Request
 *
 * Validates updates to existing ad sets
 * Security: Ensures modifications are within acceptable parameters
 */
class UpdateAdSetRequest extends FormRequest
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
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:active,paused,archived',
            'daily_budget' => 'sometimes|numeric|min:1|max:1000000',
            'lifetime_budget' => 'sometimes|numeric|min:1|max:10000000',
            'bid_strategy' => 'sometimes|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'bid_amount' => 'sometimes|numeric|min:0.01',
            'targeting' => 'sometimes|array',
            'targeting.age_min' => 'sometimes|integer|min:13|max:65',
            'targeting.age_max' => 'sometimes|integer|min:13|max:65',
            'targeting.genders' => 'sometimes|array',
            'targeting.locations' => 'sometimes|array',
            'targeting.interests' => 'sometimes|array',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
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
            'name.max' => 'Ad set name cannot exceed 255 characters',
            'daily_budget.min' => 'Daily budget must be at least 1',
            'daily_budget.max' => 'Daily budget cannot exceed 1,000,000',
            'lifetime_budget.min' => 'Lifetime budget must be at least 1',
            'lifetime_budget.max' => 'Lifetime budget cannot exceed 10,000,000',
            'targeting.age_min.min' => 'Minimum age must be at least 13',
            'targeting.age_max.max' => 'Maximum age cannot exceed 65',
            'end_time.after' => 'End time must be after start time',
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
            'daily_budget' => 'daily budget',
            'lifetime_budget' => 'lifetime budget',
            'bid_strategy' => 'bid strategy',
            'bid_amount' => 'bid amount',
        ];
    }
}

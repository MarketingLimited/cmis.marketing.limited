<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ad Set Request
 *
 * Validates creation of new ad sets
 * Security: Ensures targeting and budget parameters are valid
 */
class StoreAdSetRequest extends FormRequest
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
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,campaign_id',
            'ad_account_id' => 'required|uuid|exists:cmis_platform.ad_accounts,ad_account_id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,paused,archived',
            'daily_budget' => 'nullable|numeric|min:1|max:1000000',
            'lifetime_budget' => 'nullable|numeric|min:1|max:10000000',
            'bid_strategy' => 'nullable|in:lowest_cost,cost_cap,bid_cap,target_cost',
            'bid_amount' => 'nullable|numeric|min:0.01',
            'targeting' => 'nullable|array',
            'targeting.age_min' => 'nullable|integer|min:13|max:65',
            'targeting.age_max' => 'nullable|integer|min:13|max:65',
            'targeting.genders' => 'nullable|array',
            'targeting.locations' => 'nullable|array',
            'targeting.interests' => 'nullable|array',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
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
            'campaign_id.required' => 'Campaign ID is required',
            'campaign_id.exists' => 'Campaign not found',
            'ad_account_id.required' => 'Ad account ID is required',
            'ad_account_id.exists' => 'Ad account not found',
            'name.required' => 'Ad set name is required',
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
            'ad_account_id' => 'ad account',
            'daily_budget' => 'daily budget',
            'lifetime_budget' => 'lifetime budget',
            'bid_strategy' => 'bid strategy',
            'bid_amount' => 'bid amount',
        ];
    }
}

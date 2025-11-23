<?php

namespace App\Http\Requests\AdCampaign;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ad Campaign Request Validation
 *
 * Validates ad campaign creation across platforms
 */
class StoreAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'in:awareness,traffic,engagement,leads,conversions,sales'],
            'platform' => ['required', 'in:facebook,instagram,google,tiktok,linkedin,twitter'],
            'ad_account_id' => ['required', 'uuid', 'exists:cmis_platform.ad_accounts,ad_account_id'],
            'budget_type' => ['required', 'in:daily,lifetime'],
            'budget_amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'targeting' => ['nullable', 'array'],
            'bid_strategy' => ['nullable', 'in:lowest_cost,target_cost,cost_cap,bid_cap'],
            'optimization_goal' => ['nullable', 'in:reach,impressions,clicks,conversions,value'],
            'status' => ['nullable', 'in:draft,active,paused,completed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required',
            'objective.required' => 'Campaign objective is required',
            'platform.required' => 'Advertising platform is required',
            'budget_amount.min' => 'Budget must be at least 1',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
        ];
    }
}

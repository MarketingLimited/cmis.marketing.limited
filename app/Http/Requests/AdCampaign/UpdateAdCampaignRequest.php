<?php

namespace App\Http\Requests\AdCampaign;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Ad Campaign Request Validation
 */
class UpdateAdCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'budget_type' => ['sometimes', 'required', 'in:daily,lifetime'],
            'budget_amount' => ['sometimes', 'required', 'numeric', 'min:1'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'targeting' => ['nullable', 'array'],
            'bid_strategy' => ['nullable', 'in:lowest_cost,target_cost,cost_cap,bid_cap'],
            'optimization_goal' => ['nullable', 'in:reach,impressions,clicks,conversions,value'],
            'status' => ['nullable', 'in:draft,active,paused,completed'],
        ];
    }
}

<?php

namespace App\Http\Requests\CampaignWizard;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Campaign Wizard Request Validation
 *
 * Validates multi-step campaign wizard creation
 */
class StoreCampaignWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1: Campaign Details
            'campaign_name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'in:awareness,consideration,conversion'],
            'campaign_type' => ['required', 'in:single_platform,multi_platform,unified'],

            // Step 2: Platform Selection
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['in:facebook,instagram,google,tiktok,linkedin,twitter,snapchat'],

            // Step 3: Budget & Schedule
            'total_budget' => ['required', 'numeric', 'min:10'],
            'budget_allocation' => ['required', 'in:automatic,manual'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],

            // Step 4: Targeting
            'target_audience' => ['required', 'array'],
            'target_audience.locations' => ['required', 'array', 'min:1'],
            'target_audience.age_range' => ['required', 'array'],
            'target_audience.age_range.min' => ['required', 'integer', 'min:13'],
            'target_audience.age_range.max' => ['required', 'integer', 'max:65', 'gte:target_audience.age_range.min'],

            // Step 5: Creative
            'creative_approach' => ['required', 'in:use_existing,create_new,ai_generate'],
            'creative_ids' => ['nullable', 'array'],
            'creative_ids.*' => ['uuid', 'exists:cmis.creative_assets,creative_asset_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'campaign_name.required' => 'Campaign name is required',
            'objective.required' => 'Campaign objective is required',
            'platforms.required' => 'At least one platform must be selected',
            'total_budget.required' => 'Total budget is required',
            'total_budget.min' => 'Minimum budget is 10',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
            'target_audience.locations.required' => 'At least one location must be selected',
            'target_audience.age_range.min.required' => 'Minimum age is required',
        ];
    }
}

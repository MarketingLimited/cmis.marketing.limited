<?php

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Campaign Automation Request Validation
 *
 * Validates automated campaign rule creation
 */
class StoreCampaignAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:metric_threshold,schedule,event,manual'],
            'trigger_conditions' => ['required', 'array'],
            'trigger_conditions.*.metric' => ['required', 'string'],
            'trigger_conditions.*.operator' => ['required', 'in:greater_than,less_than,equals,changes_by'],
            'trigger_conditions.*.value' => ['required', 'numeric'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'in:pause_campaign,adjust_budget,change_bid,send_alert,restart_campaign'],
            'actions.*.parameters' => ['nullable', 'array'],
            'campaign_ids' => ['nullable', 'array'],
            'campaign_ids.*' => ['uuid', 'exists:cmis.campaigns,campaign_id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Automation name is required',
            'trigger_type.required' => 'Trigger type is required',
            'trigger_conditions.required' => 'At least one trigger condition is required',
            'actions.required' => 'At least one action is required',
            'actions.min' => 'At least one action is required',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Campaign::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_name' => ['required', 'string', 'max:255'],
            'campaign_type' => ['required', 'string', 'in:awareness,consideration,conversion,retention'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_audience' => ['nullable', 'array'],
            'objectives' => ['nullable', 'array'],
            'kpis' => ['nullable', 'array'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['integer', 'exists:cmis.channels,channel_id'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'campaign_name.required' => 'The campaign name is required.',
            'campaign_type.required' => 'Please select a campaign type.',
            'campaign_type.in' => 'Invalid campaign type selected.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure org_id is set from session
        $this->merge([
            'org_id' => session('current_org_id'),
            'created_by' => auth()->id(),
        ]);
    }
}

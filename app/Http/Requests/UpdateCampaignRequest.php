<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        return $this->user()->can('update', $campaign);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaign_name' => ['sometimes', 'string', 'max:255'],
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
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
            'campaign_type.in' => 'Invalid campaign type selected.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}

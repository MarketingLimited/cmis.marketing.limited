<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');

        // If campaign is not loaded, try to find it by ID
        if (!$campaign) {
            $campaignId = $this->route('campaignId');
            if ($campaignId) {
                $campaign = \App\Models\Campaign::find($campaignId);
            }
        }

        return $campaign && $this->user()->can('update', $campaign);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'objective' => ['sometimes', 'string'],
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'budget' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'target_audience' => ['sometimes', 'array'],
            'objectives' => ['sometimes', 'array'],
            'kpis' => ['sometimes', 'array'],
            'channels' => ['sometimes', 'array'],
            'channels.*' => ['integer', 'exists:cmis.channels,channel_id'],
            'metadata' => ['sometimes', 'array'],
            'tags' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'اسم الحملة يجب أن لا يتجاوز 255 حرف',
            'campaign_type.in' => 'نوع الحملة غير صالح',
            'status.in' => 'حالة الحملة غير صالحة',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'budget.numeric' => 'الميزانية يجب أن تكون رقم',
            'budget.min' => 'الميزانية يجب أن تكون صفر أو أكثر',
            'currency.size' => 'رمز العملة يجب أن يكون 3 أحرف',
            'channels.*.exists' => 'القناة المحددة غير موجودة',
        ];
    }
}

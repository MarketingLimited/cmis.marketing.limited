<?php

namespace App\Http\Requests\Campaign;

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
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string'],
            'campaign_type' => ['nullable', 'string', 'in:awareness,consideration,conversion,retention'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
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
            'name.required' => 'اسم الحملة مطلوب',
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $orgId = $this->route('orgId') ?? session('current_org_id');

        $this->merge([
            'org_id' => $orgId,
            'created_by' => auth()->id(),
            'status' => $this->status ?? 'draft',
            'currency' => $this->currency ?? 'BHD',
        ]);
    }

    /**
     * Get validated data with defaults.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            $data['org_id'] = $this->org_id;
            $data['created_by'] = $this->created_by;
        }

        return $data;
    }
}

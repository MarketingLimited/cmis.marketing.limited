<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreativeBriefRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'brief_title' => 'required|string|max:255',
            'brief_type' => 'required|string|in:campaign,content,design,video,social',
            'campaign_id' => 'nullable|exists:cmis.campaigns,campaign_id',
            'target_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'objectives' => 'required|string',
            'key_message' => 'nullable|string',
            'creative_strategy' => 'nullable|string',
            'target_audience' => 'required|string',
            'persona' => 'nullable|string',
            'deliverables' => 'nullable|array',
            'deliverables.*' => 'string',
            'technical_specs' => 'nullable|string',
            'brand_guidelines' => 'nullable|string',
            'references' => 'nullable|string',
            'avoid' => 'nullable|string',
            'status' => 'nullable|string|in:draft,review,approved,active,completed',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'brief_title.required' => 'عنوان البريف مطلوب',
            'brief_title.max' => 'عنوان البريف يجب ألا يتجاوز 255 حرف',
            'brief_type.required' => 'نوع البريف مطلوب',
            'brief_type.in' => 'نوع البريف غير صحيح',
            'campaign_id.exists' => 'الحملة المحددة غير موجودة',
            'target_date.date' => 'التاريخ المستهدف غير صحيح',
            'budget.numeric' => 'الميزانية يجب أن تكون رقم',
            'budget.min' => 'الميزانية يجب أن تكون أكبر من أو تساوي صفر',
            'objectives.required' => 'الأهداف مطلوبة',
            'target_audience.required' => 'الجمهور المستهدف مطلوب',
            'status.in' => 'الحالة غير صحيحة',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'brief_title' => 'عنوان البريف',
            'brief_type' => 'نوع البريف',
            'campaign_id' => 'الحملة',
            'target_date' => 'التاريخ المستهدف',
            'budget' => 'الميزانية',
            'objectives' => 'الأهداف',
            'target_audience' => 'الجمهور المستهدف',
            'status' => 'الحالة',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'draft',
            ]);
        }
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitializeWorkflowRequest extends FormRequest
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
            'campaign_id' => 'required|exists:cmis.campaigns,campaign_id',
            'campaign_name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'custom_steps' => 'nullable|array',
            'custom_steps.*.step_name' => 'required|string|max:255',
            'custom_steps.*.step_description' => 'nullable|string',
            'custom_steps.*.expected_duration' => 'nullable|integer|min:1',
            'custom_steps.*.step_order' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'campaign_id.required' => 'معرف الحملة مطلوب',
            'campaign_id.exists' => 'الحملة المحددة غير موجودة',
            'campaign_name.required' => 'اسم الحملة مطلوب',
            'campaign_name.max' => 'اسم الحملة يجب ألا يتجاوز 255 حرف',
            'start_date.date' => 'تاريخ البدء غير صحيح',
            'end_date.date' => 'تاريخ الانتهاء غير صحيح',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'custom_steps.*.step_name.required' => 'اسم الخطوة مطلوب',
            'custom_steps.*.step_name.max' => 'اسم الخطوة يجب ألا يتجاوز 255 حرف',
            'custom_steps.*.expected_duration.integer' => 'المدة المتوقعة يجب أن تكون رقم صحيح',
            'custom_steps.*.expected_duration.min' => 'المدة المتوقعة يجب أن تكون على الأقل 1 دقيقة',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'campaign_id' => 'معرف الحملة',
            'campaign_name' => 'اسم الحملة',
            'start_date' => 'تاريخ البدء',
            'end_date' => 'تاريخ الانتهاء',
            'custom_steps' => 'الخطوات المخصصة',
        ];
    }
}

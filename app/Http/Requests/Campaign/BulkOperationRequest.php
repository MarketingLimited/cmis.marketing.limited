<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;

class BulkOperationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be checked per-campaign in the controller
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', 'in:update_status,delete,archive,duplicate,assign_tags'],
            'campaign_ids' => ['required', 'array', 'min:1'],
            'campaign_ids.*' => ['uuid', 'exists:cmis.campaigns,campaign_id'],

            // For update_status operation
            'status' => ['required_if:operation,update_status', 'string', 'in:draft,active,paused,completed,archived'],

            // For assign_tags operation
            'tags' => ['required_if:operation,assign_tags', 'array', 'min:1'],
            'tags.*' => ['string', 'max:50'],

            // General options
            'force' => ['sometimes', 'boolean'],
            'notify_users' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'operation.required' => 'نوع العملية مطلوب',
            'operation.in' => 'نوع العملية غير صالح',
            'campaign_ids.required' => 'يجب تحديد حملة واحدة على الأقل',
            'campaign_ids.min' => 'يجب تحديد حملة واحدة على الأقل',
            'campaign_ids.*.uuid' => 'معرف الحملة غير صالح',
            'campaign_ids.*.exists' => 'إحدى الحملات المحددة غير موجودة',
            'status.required_if' => 'الحالة مطلوبة عند تحديث حالة الحملات',
            'status.in' => 'حالة الحملة غير صالحة',
            'tags.required_if' => 'الوسوم مطلوبة عند تعيين الوسوم',
            'tags.min' => 'يجب تحديد وسم واحد على الأقل',
            'tags.*.max' => 'طول الوسم يجب أن لا يتجاوز 50 حرف',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'operation' => 'نوع العملية',
            'campaign_ids' => 'الحملات',
            'status' => 'الحالة',
            'tags' => 'الوسوم',
        ];
    }
}

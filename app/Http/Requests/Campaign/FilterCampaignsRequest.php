<?php

namespace App\Http\Requests\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class FilterCampaignsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        Log::info('FilterCampaignsRequest::authorize called (stub) - Authorization check not yet implemented');
        // Temporarily allow all authenticated users until permissions are set up
        // Stub implementation - Re-enable authorization check once permissions are configured
        // return $this->user()->can('viewAny', \App\Models\Campaign::class);
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
            'search' => ['sometimes', 'string', 'max:255'],
            'start_date_from' => ['sometimes', 'date'],
            'start_date_to' => ['sometimes', 'date', 'after_or_equal:start_date_from'],
            'end_date_from' => ['sometimes', 'date'],
            'end_date_to' => ['sometimes', 'date', 'after_or_equal:end_date_from'],
            'budget_min' => ['sometimes', 'numeric', 'min:0'],
            'budget_max' => ['sometimes', 'numeric', 'min:0', 'gte:budget_min'],
            'channel_id' => ['sometimes', 'integer', 'exists:cmis.channels,channel_id'],
            'created_by' => ['sometimes', 'uuid', 'exists:cmis.users,user_id'],
            'sort_by' => ['sometimes', 'string', 'in:created_at,updated_at,name,start_date,end_date,budget'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'per_page.max' => 'الحد الأقصى للعناصر في الصفحة هو 100',
            'status.in' => 'حالة الحملة غير صالحة',
            'campaign_type.in' => 'نوع الحملة غير صالح',
            'start_date_to.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد أو يساوي تاريخ البدء',
            'end_date_to.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد أو يساوي تاريخ البدء',
            'budget_max.gte' => 'الحد الأقصى للميزانية يجب أن يكون أكبر من أو يساوي الحد الأدنى',
            'channel_id.exists' => 'القناة المحددة غير موجودة',
            'created_by.exists' => 'المستخدم المحدد غير موجود',
            'sort_by.in' => 'حقل الترتيب غير صالح',
            'sort_direction.in' => 'اتجاه الترتيب يجب أن يكون asc أو desc',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults if not provided
        $this->merge([
            'per_page' => $this->per_page ?? 20,
            'page' => $this->page ?? 1,
            'sort_by' => $this->sort_by ?? 'created_at',
            'sort_direction' => $this->sort_direction ?? 'desc',
        ]);
    }
}

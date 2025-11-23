<?php

namespace App\Http\Requests\Publishing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Schedule Publish Request Validation
 *
 * Validates content publishing scheduling
 */
class SchedulePublishRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['required', 'uuid', 'exists:cmis.content_items,content_item_id'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['in:facebook,instagram,twitter,linkedin,tiktok,youtube'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'timezone' => ['required', 'timezone'],
            'publish_type' => ['nullable', 'in:immediate,scheduled,recurring'],
            'recurring_pattern' => ['nullable', 'array'],
            'recurring_pattern.frequency' => ['required_with:recurring_pattern', 'in:daily,weekly,monthly'],
            'recurring_pattern.interval' => ['required_with:recurring_pattern', 'integer', 'min:1', 'max:365'],
            'recurring_pattern.end_date' => ['nullable', 'date', 'after:scheduled_at'],
            'auto_publish' => ['nullable', 'boolean'],
            'require_approval' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'content_id.required' => 'Content ID is required',
            'content_id.exists' => 'The specified content does not exist',
            'platforms.required' => 'At least one platform must be selected',
            'scheduled_at.required' => 'Schedule time is required',
            'scheduled_at.after' => 'Schedule time must be in the future',
            'timezone.required' => 'Timezone is required',
            'timezone.timezone' => 'Invalid timezone',
        ];
    }
}

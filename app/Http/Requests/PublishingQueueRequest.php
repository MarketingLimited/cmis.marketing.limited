<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishingQueueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'social_account_id' => 'required|uuid',
            'weekdays_enabled' => 'nullable|string|size:7|regex:/^[01]{7}$/',
            'time_slots' => 'nullable|array',
            'time_slots.*.time' => 'required_with:time_slots|date_format:H:i',
            'time_slots.*.enabled' => 'nullable|boolean',
            'timezone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];

        // For update requests, make social_account_id optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['social_account_id'] = 'sometimes|required|uuid';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'social_account_id.required' => 'Social account ID is required',
            'social_account_id.uuid' => 'Social account ID must be a valid UUID',
            'weekdays_enabled.size' => 'Weekdays enabled must be exactly 7 characters (MTWTFSS)',
            'weekdays_enabled.regex' => 'Weekdays enabled must contain only 0s and 1s',
            'time_slots.array' => 'Time slots must be an array',
            'time_slots.*.time.required_with' => 'Each time slot must have a time field',
            'time_slots.*.time.date_format' => 'Time must be in HH:MM format',
            'timezone.max' => 'Timezone must not exceed 50 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'social_account_id' => 'social account',
            'weekdays_enabled' => 'weekdays configuration',
            'time_slots' => 'time slots',
            'timezone' => 'timezone',
            'is_active' => 'active status',
        ];
    }
}

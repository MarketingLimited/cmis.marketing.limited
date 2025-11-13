<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureQueueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization check
        // return $this->user()->can('manage-queue', $this->route('socialAccountId'));
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
            'org_id' => 'required|uuid',
            'social_account_id' => 'required|uuid',
            'weekdays_enabled' => [
                'nullable',
                'string',
                'regex:/^[01]{7}$/'
            ],
            'time_slots' => 'nullable|array|min:1',
            'time_slots.*' => [
                'string',
                'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/'
            ],
            'timezone' => 'nullable|timezone',
            'is_active' => 'nullable|boolean'
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'org_id.required' => 'Organization ID is required',
            'org_id.uuid' => 'Organization ID must be a valid UUID',
            'social_account_id.required' => 'Social account ID is required',
            'social_account_id.uuid' => 'Social account ID must be a valid UUID',
            'weekdays_enabled.regex' => 'Weekdays must be a 7-character string of 0s and 1s (MTWTFSS)',
            'time_slots.min' => 'At least one time slot is required',
            'time_slots.*.regex' => 'Each time slot must be in HH:MM format (24-hour)',
            'timezone.timezone' => 'Invalid timezone'
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'org_id' => 'organization',
            'social_account_id' => 'social account',
            'weekdays_enabled' => 'active weekdays',
            'time_slots' => 'posting times',
            'timezone' => 'timezone'
        ];
    }
}

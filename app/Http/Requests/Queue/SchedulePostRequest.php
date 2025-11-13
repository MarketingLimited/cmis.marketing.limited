<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class SchedulePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization check
        // return $this->user()->can('schedule-post', $this->input('post_id'));
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
            'post_id' => 'required|uuid',
            'scheduled_for' => [
                'nullable',
                'date',
                'after:now'
            ]
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'post_id.required' => 'Post ID is required',
            'post_id.uuid' => 'Post ID must be a valid UUID',
            'scheduled_for.date' => 'Scheduled time must be a valid date',
            'scheduled_for.after' => 'Scheduled time must be in the future'
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'post_id' => 'post',
            'scheduled_for' => 'scheduled time'
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation(): void
    {
        // If scheduled_for is not provided, it will be auto-assigned
        // to next available slot, so we don't require it
    }
}

<?php

namespace App\Http\Requests\Channel;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Channel Request Validation
 *
 * Validates marketing channel configuration
 */
class StoreChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:social_media,email,sms,push_notification,display_ads,search_ads,video_ads'],
            'platform' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,paused,archived'],
            'configuration' => ['nullable', 'array'],
            'budget_allocation' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Channel name is required',
            'type.required' => 'Channel type is required',
            'type.in' => 'Invalid channel type',
            'priority.min' => 'Priority must be between 1 and 10',
            'priority.max' => 'Priority must be between 1 and 10',
        ];
    }
}

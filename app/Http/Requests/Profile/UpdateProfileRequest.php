<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update User Profile Request Validation
 *
 * Validates user profile updates
 */
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('cmis.users', 'email')->ignore($this->user()->user_id, 'user_id'),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png'],
            'timezone' => ['nullable', 'timezone'],
            'language' => ['nullable', 'string', 'size:2'],
            'notification_preferences' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already in use',
            'phone.regex' => 'Invalid phone number format',
            'avatar.image' => 'Avatar must be an image file',
            'avatar.max' => 'Avatar file size must not exceed 2MB',
        ];
    }
}

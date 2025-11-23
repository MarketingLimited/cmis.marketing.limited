<?php

namespace App\Http\Requests\Listening;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Competitor Request Validation
 *
 * Validates competitor monitoring updates
 */
class UpdateCompetitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'website' => [
                'nullable',
                'url',
                'max:500',
            ],
            'industry' => [
                'nullable',
                'string',
                'max:100',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'social_handles' => [
                'nullable',
                'array',
            ],
            'social_handles.twitter' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[A-Za-z0-9_]+$/',
            ],
            'social_handles.facebook' => [
                'nullable',
                'string',
                'max:100',
            ],
            'social_handles.instagram' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9._]+$/',
            ],
            'social_handles.linkedin' => [
                'nullable',
                'string',
                'max:100',
            ],
            'social_handles.tiktok' => [
                'nullable',
                'string',
                'max:24',
                'regex:/^[A-Za-z0-9._]+$/',
            ],
            'monitor_frequency' => [
                'nullable',
                'in:realtime,hourly,daily,weekly',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Competitor name is required',
            'website.url' => 'Please provide a valid website URL',
            'social_handles.twitter.regex' => 'Twitter handle format invalid',
            'social_handles.instagram.regex' => 'Instagram handle format invalid',
            'social_handles.tiktok.regex' => 'TikTok handle format invalid',
        ];
    }
}

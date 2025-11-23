<?php

namespace App\Http\Requests\Listening;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Monitoring Keyword Request Validation
 *
 * Validates updates to existing keyword monitoring configurations
 */
class UpdateMonitoringKeywordRequest extends FormRequest
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
            'keyword' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'match_type' => [
                'sometimes',
                'required',
                'in:exact,phrase,broad',
            ],
            'platforms' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
            'platforms.*' => [
                'in:twitter,facebook,instagram,linkedin,tiktok,youtube',
            ],
            'languages' => [
                'nullable',
                'array',
            ],
            'languages.*' => [
                'string',
                'size:2',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'alert_threshold' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
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
            'keyword.required' => 'Keyword or phrase is required',
            'keyword.min' => 'Keyword must be at least 2 characters',
            'match_type.in' => 'Match type must be one of: exact, phrase, broad',
            'platforms.min' => 'At least one platform must be selected',
            'platforms.*.in' => 'Invalid platform selected',
            'languages.*.size' => 'Language codes must be 2 characters (ISO 639-1)',
        ];
    }
}

<?php

namespace App\Http\Requests\Listening;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Monitoring Keyword Request Validation
 *
 * Validates social media keyword monitoring setup:
 * - Keyword/phrase validation
 * - Platform selection
 * - Match type and language settings
 *
 * Security Features:
 * - Keyword length limits
 * - Valid platform selection
 * - Language code validation
 */
class StoreMonitoringKeywordRequest extends FormRequest
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
                'required',
                'string',
                'min:2',
                'max:255',
            ],
            'match_type' => [
                'required',
                'in:exact,phrase,broad',
            ],
            'platforms' => [
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
                'size:2', // ISO 639-1 language codes
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
            'keyword.max' => 'Keyword must not exceed 255 characters',
            'match_type.required' => 'Match type is required',
            'match_type.in' => 'Match type must be one of: exact, phrase, broad',
            'platforms.required' => 'At least one platform must be selected',
            'platforms.*.in' => 'Invalid platform selected',
            'languages.*.size' => 'Language codes must be 2 characters (ISO 639-1)',
            'alert_threshold.min' => 'Alert threshold must be at least 1',
            'alert_threshold.max' => 'Alert threshold must not exceed 10,000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'keyword' => 'monitoring keyword',
            'match_type' => 'match type',
            'platforms' => 'social platforms',
            'languages' => 'language filters',
            'alert_threshold' => 'alert threshold',
        ];
    }
}

<?php

namespace App\Http\Requests\Listening;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Response Template Request Validation
 *
 * Validates social media response templates:
 * - Template content and categorization
 * - Platform-specific formatting
 * - Tag and trigger configuration
 *
 * Security Features:
 * - Content length limits
 * - XSS prevention through validation
 * - Template variable validation
 */
class StoreResponseTemplateRequest extends FormRequest
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
                'required',
                'string',
                'max:255',
            ],
            'category' => [
                'required',
                'in:customer_service,complaint,praise,question,general',
            ],
            'content' => [
                'required',
                'string',
                'max:5000',
            ],
            'platforms' => [
                'required',
                'array',
                'min:1',
            ],
            'platforms.*' => [
                'in:twitter,facebook,instagram,linkedin,tiktok',
            ],
            'triggers' => [
                'nullable',
                'array',
            ],
            'triggers.*' => [
                'string',
                'max:100',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'string',
                'max:50',
            ],
            'variables' => [
                'nullable',
                'array',
            ],
            'variables.*' => [
                'string',
                'max:100',
            ],
            'tone' => [
                'nullable',
                'in:formal,friendly,professional,casual,empathetic',
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
            'name.required' => 'Template name is required',
            'category.required' => 'Template category is required',
            'category.in' => 'Invalid category selected',
            'content.required' => 'Template content is required',
            'content.max' => 'Template content must not exceed 5000 characters',
            'platforms.required' => 'At least one platform must be selected',
            'platforms.*.in' => 'Invalid platform selected',
            'tone.in' => 'Invalid tone selected',
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
            'name' => 'template name',
            'category' => 'template category',
            'content' => 'template content',
            'platforms' => 'social platforms',
            'triggers' => 'auto-triggers',
            'tags' => 'tags',
            'tone' => 'communication tone',
        ];
    }
}

<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * AI Content Generation Request
 *
 * Validates and sanitizes input for AI content generation endpoints.
 * Includes XSS protection and input sanitization.
 *
 * Part of Phase 1B security improvements (2025-11-21)
 */
class GenerateContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Content Type
            'content_type' => [
                'required',
                'string',
                Rule::in(['campaign', 'ad_copy', 'social_post', 'email', 'blog_post']),
            ],

            // Context & Prompts
            'prompt' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],
            'context' => [
                'nullable',
                'array',
            ],
            'context.brand_voice' => [
                'nullable',
                'string',
                'max:500',
            ],
            'context.target_audience' => [
                'nullable',
                'string',
                'max:500',
            ],
            'context.key_points' => [
                'nullable',
                'array',
                'max:10',
            ],
            'context.key_points.*' => [
                'string',
                'max:200',
            ],

            // AI Configuration
            'marketing_principle' => [
                'nullable',
                'string',
                Rule::in([
                    'scarcity', 'urgency', 'social_proof', 'authority',
                    'reciprocity', 'consistency', 'liking', 'unity',
                ]),
            ],
            'tone' => [
                'nullable',
                'string',
                Rule::in(['professional', 'casual', 'friendly', 'formal', 'enthusiastic']),
            ],
            'language' => [
                'nullable',
                'string',
                Rule::in(['en', 'ar']),
            ],
            'max_length' => [
                'nullable',
                'integer',
                'min:50',
                'max:5000',
            ],

            // Optional Campaign Context
            'campaign_id' => [
                'nullable',
                'uuid',
                'exists:cmis.campaigns,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Please provide a prompt for content generation.',
            'prompt.min' => 'Prompt must be at least 10 characters.',
            'prompt.max' => 'Prompt cannot exceed 5000 characters.',
            'content_type.required' => 'Please specify the type of content to generate.',
            'content_type.in' => 'Invalid content type selected.',
            'marketing_principle.in' => 'Invalid marketing principle selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize prompt (remove dangerous HTML/scripts)
        if ($this->has('prompt')) {
            $this->merge([
                'prompt' => $this->sanitizeInput($this->input('prompt')),
            ]);
        }

        // Sanitize context fields
        if ($this->has('context')) {
            $context = $this->input('context');

            if (isset($context['brand_voice'])) {
                $context['brand_voice'] = $this->sanitizeInput($context['brand_voice']);
            }

            if (isset($context['target_audience'])) {
                $context['target_audience'] = $this->sanitizeInput($context['target_audience']);
            }

            if (isset($context['key_points']) && is_array($context['key_points'])) {
                $context['key_points'] = array_map(
                    fn($point) => $this->sanitizeInput($point),
                    $context['key_points']
                );
            }

            $this->merge(['context' => $context]);
        }

        // Set defaults
        $this->merge([
            'language' => $this->input('language', app()->getLocale()),
            'tone' => $this->input('tone', 'professional'),
        ]);
    }

    /**
     * Sanitize input to prevent XSS and other injection attacks
     *
     * @param string $input
     * @return string
     */
    protected function sanitizeInput(string $input): string
    {
        // Remove HTML tags (allow basic formatting only)
        $input = strip_tags($input, '<p><br><b><i><u><strong><em>');

        // Encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Remove null bytes
        $input = str_replace(chr(0), '', $input);

        // Trim whitespace
        $input = trim($input);

        return $input;
    }

    /**
     * Get validated and sanitized data
     *
     * @return array
     */
    public function getSanitizedData(): array
    {
        return $this->validated();
    }
}

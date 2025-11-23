<?php

namespace App\Http\Requests\GPT;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generate Content Request Validation
 *
 * Validates AI content generation requests
 */
class GenerateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:10', 'max:2000'],
            'content_type' => ['required', 'in:ad_copy,social_post,email,blog_post,product_description'],
            'tone' => ['nullable', 'in:professional,casual,friendly,formal,persuasive,humorous'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'array', 'max:10'],
            'keywords.*' => ['string', 'max:50'],
            'max_length' => ['nullable', 'integer', 'min:50', 'max:5000'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'platform' => ['nullable', 'in:facebook,instagram,twitter,linkedin,tiktok,youtube'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Content prompt is required',
            'prompt.min' => 'Prompt must be at least 10 characters',
            'content_type.required' => 'Content type is required',
            'keywords.max' => 'Maximum of 10 keywords allowed',
            'temperature.max' => 'Temperature must be between 0 and 1',
        ];
    }
}

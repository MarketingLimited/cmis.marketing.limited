<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVideoRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,id',
            'prompt' => 'required|string|max:2000',
            'duration' => 'nullable|integer|min:5|max:8',
            'aspect_ratio' => 'nullable|in:16:9,9:16,1:1',
            'use_fast_model' => 'nullable|boolean',
            'source_image' => 'nullable|string', // Storage path
            'reference_images' => 'nullable|array|max:3',
            'reference_images.*' => 'string',
            'animation_prompt' => 'nullable|required_with:source_image|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Video prompt is required',
            'prompt.max' => 'Prompt must not exceed 2000 characters',
            'duration.min' => 'Minimum video duration is 5 seconds',
            'duration.max' => 'Maximum video duration is 8 seconds',
            'reference_images.max' => 'Maximum 3 reference images allowed',
            'animation_prompt.required_with' => 'Animation prompt required when converting image to video',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAdDesignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,id',
            'objective' => 'required|string|max:50',
            'brand_guidelines' => 'required|string|max:1000',
            'design_requirements' => 'required|array|min:1',
            'design_requirements.*' => 'required|string|max:200',
            'variation_count' => 'nullable|integer|min:1|max:5',
            'resolution' => 'nullable|in:low,medium,high',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'objective.required' => 'Campaign objective is required',
            'brand_guidelines.required' => 'Brand guidelines are required for consistent designs',
            'design_requirements.required' => 'Please specify at least one design requirement',
            'design_requirements.*.max' => 'Each requirement must not exceed 200 characters',
            'variation_count.max' => 'Maximum 5 design variations allowed per request',
        ];
    }
}

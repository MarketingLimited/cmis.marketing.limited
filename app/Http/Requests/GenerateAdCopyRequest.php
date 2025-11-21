<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAdCopyRequest extends FormRequest
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
            'objective' => 'required|string|max:50',
            'target_audience' => 'required|string|max:500',
            'product_description' => 'required|string|max:1000',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:200',
            'tone' => 'nullable|in:professional,casual,friendly,urgent,luxury',
            'language' => 'nullable|string|size:2',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'objective.required' => 'Campaign objective is required',
            'target_audience.required' => 'Target audience description is required',
            'product_description.required' => 'Product/service description is required',
        ];
    }
}

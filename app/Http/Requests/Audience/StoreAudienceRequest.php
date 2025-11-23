<?php

namespace App\Http\Requests\Audience;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Audience Request Validation
 *
 * Validates custom audience creation and targeting
 */
class StoreAudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:custom,lookalike,saved,retargeting'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'in:customer_list,website_traffic,app_activity,engagement'],
            'targeting' => ['nullable', 'array'],
            'targeting.locations' => ['nullable', 'array'],
            'targeting.age_min' => ['nullable', 'integer', 'min:13', 'max:65'],
            'targeting.age_max' => ['nullable', 'integer', 'min:13', 'max:65', 'gte:targeting.age_min'],
            'targeting.genders' => ['nullable', 'array'],
            'targeting.genders.*' => ['in:male,female,all'],
            'targeting.interests' => ['nullable', 'array'],
            'targeting.behaviors' => ['nullable', 'array'],
            'targeting.languages' => ['nullable', 'array'],
            'exclusions' => ['nullable', 'array'],
            'size_estimate' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Audience name is required',
            'type.required' => 'Audience type is required',
            'targeting.age_min.min' => 'Minimum age must be at least 13',
            'targeting.age_max.gte' => 'Maximum age must be greater than or equal to minimum age',
        ];
    }
}

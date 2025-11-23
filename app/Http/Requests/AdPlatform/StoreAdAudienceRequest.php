<?php

namespace App\Http\Requests\AdPlatform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ad Audience Request
 *
 * Validates creation of new custom audiences
 * Security: Ensures audience data and targeting parameters are valid
 */
class StoreAdAudienceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and policies
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
            'ad_account_id' => 'required|uuid|exists:cmis_platform.ad_accounts,ad_account_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'audience_type' => 'required|in:custom,lookalike,saved',
            'source_audience_id' => 'nullable|uuid|exists:cmis_platform.ad_audiences,audience_id',
            'lookalike_ratio' => 'nullable|numeric|min:1|max:10',
            'targeting_spec' => 'nullable|array',
            'size_estimate' => 'nullable|integer|min:0',
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
            'ad_account_id.required' => 'Ad account ID is required',
            'ad_account_id.exists' => 'Ad account not found',
            'name.required' => 'Audience name is required',
            'name.max' => 'Audience name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1,000 characters',
            'audience_type.required' => 'Audience type is required',
            'audience_type.in' => 'Invalid audience type. Supported: custom, lookalike, saved',
            'source_audience_id.exists' => 'Source audience not found',
            'lookalike_ratio.min' => 'Lookalike ratio must be at least 1',
            'lookalike_ratio.max' => 'Lookalike ratio cannot exceed 10',
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
            'ad_account_id' => 'ad account',
            'audience_type' => 'audience type',
            'source_audience_id' => 'source audience',
            'lookalike_ratio' => 'lookalike ratio',
            'targeting_spec' => 'targeting specification',
        ];
    }
}

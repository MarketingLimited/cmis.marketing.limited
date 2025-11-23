<?php

namespace App\Http\Requests\Compliance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Compliance Check Request Validation
 *
 * Validates compliance verification requests:
 * - COPPA compliance for children's advertising
 * - GDPR compliance for EU data protection
 * - Platform-specific ad policies
 *
 * Security Features:
 * - Age restriction validation
 * - Geographic targeting validation
 * - Content category validation
 */
class StoreComplianceCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_id' => ['required', 'uuid', 'exists:cmis.content_items,content_item_id'],
            'campaign_id' => ['nullable', 'uuid', 'exists:cmis.campaigns,campaign_id'],
            'check_type' => ['required', 'in:coppa,gdpr,platform_policy,all'],
            'target_platforms' => ['required', 'array', 'min:1'],
            'target_platforms.*' => ['in:facebook,instagram,twitter,linkedin,tiktok,youtube,google'],
            'target_age_range' => ['nullable', 'array'],
            'target_age_range.min' => ['nullable', 'integer', 'min:0', 'max:100'],
            'target_age_range.max' => ['nullable', 'integer', 'min:0', 'max:100', 'gte:target_age_range.min'],
            'geographic_targeting' => ['nullable', 'array'],
            'geographic_targeting.countries' => ['nullable', 'array'],
            'geographic_targeting.countries.*' => ['string', 'size:2'], // ISO 3166-1
            'content_category' => ['required', 'in:general,alcohol,gambling,healthcare,financial,political,adult'],
            'data_collection' => ['nullable', 'array'],
            'data_collection.personal_info' => ['nullable', 'boolean'],
            'data_collection.behavioral_tracking' => ['nullable', 'boolean'],
            'data_collection.third_party_sharing' => ['nullable', 'boolean'],
            'coppa_attestation' => ['required_if:check_type,coppa,all', 'boolean'],
            'gdpr_attestation' => ['required_if:check_type,gdpr,all', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'content_id.required' => 'Content ID is required for compliance check',
            'content_id.exists' => 'The specified content does not exist',
            'check_type.required' => 'Compliance check type is required',
            'target_platforms.required' => 'At least one target platform must be selected',
            'target_age_range.max.gte' => 'Maximum age must be greater than or equal to minimum age',
            'content_category.required' => 'Content category is required for compliance check',
            'content_category.in' => 'Invalid content category',
            'coppa_attestation.required_if' => 'COPPA compliance attestation is required',
            'gdpr_attestation.required_if' => 'GDPR compliance attestation is required',
            'geographic_targeting.countries.*.size' => 'Country codes must be 2 characters (ISO 3166-1)',
        ];
    }

    public function attributes(): array
    {
        return [
            'content_id' => 'content',
            'check_type' => 'compliance check type',
            'target_platforms' => 'target platforms',
            'content_category' => 'content category',
            'coppa_attestation' => 'COPPA attestation',
            'gdpr_attestation' => 'GDPR attestation',
        ];
    }

    /**
     * Additional validation after standard rules
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // If targeting children (under 13), require COPPA compliance
            if (isset($this->target_age_range['min']) && $this->target_age_range['min'] < 13) {
                if (!$this->coppa_attestation) {
                    $validator->errors()->add('coppa_attestation', 'COPPA compliance is required when targeting children under 13');
                }
            }

            // If targeting EU countries, require GDPR compliance
            $euCountries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
            if (isset($this->geographic_targeting['countries'])) {
                $targetingEU = !empty(array_intersect($this->geographic_targeting['countries'], $euCountries));
                if ($targetingEU && !$this->gdpr_attestation) {
                    $validator->errors()->add('gdpr_attestation', 'GDPR compliance is required when targeting EU countries');
                }
            }
        });
    }
}

<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Lead Request Validation
 *
 * Validates lead capture and creation:
 * - Contact information
 * - Lead source tracking
 * - GDPR consent
 *
 * Security Features:
 * - Email validation
 * - Phone format validation
 * - GDPR consent required
 */
class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'source' => ['required', 'in:website,social_media,referral,paid_ad,event,other'],
            'campaign_id' => ['nullable', 'uuid', 'exists:cmis.campaigns,campaign_id'],
            'lead_status' => ['nullable', 'in:new,contacted,qualified,unqualified,converted'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'custom_fields' => ['nullable', 'array'],
            'gdpr_consent' => ['required', 'boolean', 'accepted'],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'phone.regex' => 'Invalid phone number format',
            'source.required' => 'Lead source is required',
            'gdpr_consent.required' => 'GDPR consent is required',
            'gdpr_consent.accepted' => 'You must accept the privacy policy',
        ];
    }
}

<?php

namespace App\Http\Requests\Enterprise;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Enterprise Request Validation
 *
 * Validates enterprise account creation/configuration
 */
class StoreEnterpriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'in:1-10,11-50,51-200,201-500,501-1000,1000+'],
            'website' => ['nullable', 'url', 'max:500'],
            'billing_address' => ['required', 'array'],
            'billing_address.street' => ['required', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:100'],
            'billing_address.state' => ['nullable', 'string', 'max:100'],
            'billing_address.postal_code' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'size:2'],
            'billing_contact' => ['required', 'array'],
            'billing_contact.name' => ['required', 'string', 'max:255'],
            'billing_contact.email' => ['required', 'email', 'max:255'],
            'billing_contact.phone' => ['required', 'string', 'max:20'],
            'payment_method' => ['required', 'in:invoice,credit_card,wire_transfer,ach'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'sla_tier' => ['nullable', 'in:standard,premium,enterprise'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Company name is required',
            'legal_name.required' => 'Legal company name is required',
            'billing_address.required' => 'Billing address is required',
            'billing_contact.required' => 'Billing contact information is required',
            'payment_method.required' => 'Payment method is required',
            'contract_start_date.required' => 'Contract start date is required',
            'contract_end_date.after' => 'Contract end date must be after start date',
        ];
    }
}

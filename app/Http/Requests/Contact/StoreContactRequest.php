<?php

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Contact Request Validation
 *
 * Validates contact/customer creation
 */
class StoreContactRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', 'unique:cmis.contacts,email'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'array'],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.city' => ['nullable', 'string', 'max:100'],
            'address.state' => ['nullable', 'string', 'max:100'],
            'address.postal_code' => ['nullable', 'string', 'max:20'],
            'address.country' => ['nullable', 'string', 'size:2'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
            'gdpr_consent' => ['required', 'boolean', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email address is already registered',
            'phone.regex' => 'Invalid phone number format',
            'address.country.size' => 'Country code must be 2 characters (ISO 3166-1)',
            'gdpr_consent.accepted' => 'Privacy policy consent is required',
        ];
    }
}

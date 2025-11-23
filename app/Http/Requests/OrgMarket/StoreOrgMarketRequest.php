<?php

namespace App\Http\Requests\OrgMarket;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Organization Market Request Validation
 *
 * Validates market/region configuration for organizations
 */
class StoreOrgMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'],
            'region' => ['nullable', 'string', 'max:100'],
            'language' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'timezone' => ['required', 'timezone'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'market_name.required' => 'Market name is required',
            'country_code.required' => 'Country code is required',
            'country_code.size' => 'Country code must be 2 characters (ISO 3166-1)',
            'language.size' => 'Language code must be 2 characters (ISO 639-1)',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217)',
            'timezone.timezone' => 'Invalid timezone',
        ];
    }
}

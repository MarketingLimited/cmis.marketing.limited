<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Organization Request
 *
 * Validates creation of new organizations
 * Security: Ensures proper validation before org creation
 */
class StoreOrgRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by OrgPolicy
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
            'name' => 'required|string|max:255',
            'default_locale' => 'nullable|string|max:10|in:ar-BH,en-US,en-GB',
            'currency' => 'nullable|string|size:3|in:BHD,USD,EUR,GBP,SAR,AED,KWD',
            'provider' => 'nullable|string|max:100',
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
            'name.required' => 'Organization name is required',
            'name.max' => 'Organization name cannot exceed 255 characters',
            'default_locale.in' => 'Invalid locale. Supported: ar-BH, en-US, en-GB',
            'currency.size' => 'Currency code must be exactly 3 characters',
            'currency.in' => 'Invalid currency. Supported: BHD, USD, EUR, GBP, SAR, AED, KWD',
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
            'default_locale' => 'default locale',
        ];
    }
}

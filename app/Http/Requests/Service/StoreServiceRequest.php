<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Service Request Validation
 *
 * Validates service offering creation
 */
class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'pricing_model' => ['required', 'in:fixed,hourly,monthly,custom'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'duration_hours' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Service name is required',
            'pricing_model.required' => 'Pricing model is required',
            'pricing_model.in' => 'Invalid pricing model',
            'price.required' => 'Price is required',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217)',
        ];
    }
}

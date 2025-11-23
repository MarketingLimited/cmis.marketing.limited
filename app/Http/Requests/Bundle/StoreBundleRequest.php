<?php

namespace App\Http\Requests\Bundle;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Bundle Request Validation
 *
 * Validates product/service bundle creation
 */
class StoreBundleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'product_ids' => ['required', 'array', 'min:2'],
            'product_ids.*' => ['uuid', 'exists:cmis.products,product_id'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['uuid', 'exists:cmis.services,service_id'],
            'bundle_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after:valid_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Bundle name is required',
            'product_ids.required' => 'At least 2 products are required for a bundle',
            'product_ids.min' => 'At least 2 products are required for a bundle',
            'bundle_price.required' => 'Bundle price is required',
            'discount_percentage.max' => 'Discount cannot exceed 100%',
        ];
    }
}

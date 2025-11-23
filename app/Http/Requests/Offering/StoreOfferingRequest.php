<?php

namespace App\Http\Requests\Offering;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Offering Request Validation
 *
 * Validates special offer/promotion creation
 */
class StoreOfferingRequest extends FormRequest
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
            'type' => ['required', 'in:discount,bundle,limited_time,seasonal,promotional'],
            'discount_type' => ['nullable', 'in:percentage,fixed_amount'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date', 'before_or_equal:end_date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['uuid'],
            'terms_conditions' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Offering name is required',
            'type.required' => 'Offering type is required',
            'start_date.required' => 'Start date is required',
            'end_date.required' => 'End date is required',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
        ];
    }
}

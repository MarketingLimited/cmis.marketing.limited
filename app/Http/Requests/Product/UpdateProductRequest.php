<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Product Request Validation
 *
 * Validates product updates with partial field support
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('cmis.products', 'sku')->ignore($this->route('product')),
            ],
            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'brand' => [
                'nullable',
                'string',
                'max:100',
            ],
            'stock_quantity' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'attributes' => [
                'nullable',
                'array',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'is_featured' => [
                'nullable',
                'boolean',
            ],
            'url' => [
                'nullable',
                'url',
                'max:500',
            ],
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
            'sku.unique' => 'This SKU is already in use',
            'price.min' => 'Price must be 0 or greater',
        ];
    }
}

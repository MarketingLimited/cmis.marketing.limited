<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Product Request Validation
 *
 * Validates product/service catalog creation:
 * - Product details and pricing
 * - Category and attributes
 * - Image validation
 *
 * Security Features:
 * - Price validation
 * - SKU uniqueness
 * - Image file validation
 */
class StoreProductRequest extends FormRequest
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
                'unique:cmis.products,sku',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'currency' => [
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
            'images' => [
                'nullable',
                'array',
                'max:10', // Max 10 images
            ],
            'images.*' => [
                'file',
                'image',
                'max:10240', // 10MB max
                'mimes:jpeg,jpg,png,webp',
            ],
            'attributes' => [
                'nullable',
                'array',
            ],
            'attributes.*.name' => [
                'required_with:attributes',
                'string',
                'max:100',
            ],
            'attributes.*.value' => [
                'required_with:attributes',
                'string',
                'max:255',
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
            'name.required' => 'Product name is required',
            'sku.unique' => 'This SKU is already in use',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be 0 or greater',
            'currency.size' => 'Currency code must be 3 characters (ISO 4217)',
            'currency.regex' => 'Currency code must be uppercase letters',
            'images.max' => 'Maximum of 10 images allowed',
            'images.*.image' => 'File must be an image',
            'images.*.max' => 'Image file size must not exceed 10MB',
            'images.*.mimes' => 'Image must be JPEG, PNG, or WebP format',
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
            'name' => 'product name',
            'sku' => 'SKU',
            'price' => 'product price',
            'currency' => 'currency code',
            'stock_quantity' => 'stock quantity',
            'images' => 'product images',
        ];
    }
}

<?php

namespace App\Http\Requests\Creative;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreativeAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\CreativeAsset::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', 'string', 'in:image,video,document,audio,other'],
            'file' => ['required', 'file', 'max:102400'], // 100MB max
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'asset_name.required' => 'اسم المادة الإبداعية مطلوب',
            'asset_type.required' => 'نوع المادة مطلوب',
            'asset_type.in' => 'نوع المادة غير صالح',
            'file.required' => 'الملف مطلوب',
            'file.max' => 'حجم الملف يجب أن لا يتجاوز 100 ميجابايت',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'org_id' => session('current_org_id') ?? auth()->user()->org_id,
        ]);
    }
}

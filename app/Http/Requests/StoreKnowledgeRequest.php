<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'domain' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'topic' => 'required|string|max:255',
            'content' => 'required|string',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:50',
            'source_url' => 'nullable|url|max:500',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'is_verified' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'المجال مطلوب',
            'domain.max' => 'المجال يجب ألا يتجاوز 100 حرف',
            'category.required' => 'الفئة مطلوبة',
            'category.max' => 'الفئة يجب ألا تتجاوز 100 حرف',
            'topic.required' => 'الموضوع مطلوب',
            'topic.max' => 'الموضوع يجب ألا يتجاوز 255 حرف',
            'content.required' => 'المحتوى مطلوب',
            'source_url.url' => 'رابط المصدر غير صحيح',
            'source_url.max' => 'رابط المصدر يجب ألا يتجاوز 500 حرف',
            'confidence_score.numeric' => 'درجة الثقة يجب أن تكون رقم',
            'confidence_score.min' => 'درجة الثقة يجب أن تكون بين 0 و 1',
            'confidence_score.max' => 'درجة الثقة يجب أن تكون بين 0 و 1',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'domain' => 'المجال',
            'category' => 'الفئة',
            'topic' => 'الموضوع',
            'content' => 'المحتوى',
            'keywords' => 'الكلمات المفتاحية',
            'source_url' => 'رابط المصدر',
            'confidence_score' => 'درجة الثقة',
            'is_verified' => 'حالة التحقق',
            'metadata' => 'البيانات الإضافية',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults
        if ($this->has('is_verified')) {
            $this->merge([
                'is_verified' => $this->boolean('is_verified'),
            ]);
        }

        if (!$this->has('confidence_score')) {
            $this->merge([
                'confidence_score' => 0.8,
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\Creative;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreativeAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $asset = $this->route('asset') ?? \App\Models\CreativeAsset::find($this->route('assetId'));
        return $asset && $this->user()->can('update', $asset);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'asset_name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:pending_review,approved,rejected,archived'],
            'metadata' => ['sometimes', 'array'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'rejection_reason' => ['required_if:status,rejected', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'حالة المادة غير صالحة',
            'rejection_reason.required_if' => 'سبب الرفض مطلوب عند رفض المادة',
        ];
    }
}

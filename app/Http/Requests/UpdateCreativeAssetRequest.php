<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreativeAssetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $asset = $this->route('creativeAsset');
        return $this->user()->can('update', $asset);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_name' => ['sometimes', 'string', 'max:255'],
            'asset_type' => ['sometimes', 'string', 'in:image,video,audio,document,template'],
            'file_url' => ['sometimes', 'string', 'max:1000'],
            'file' => ['sometimes', 'file', 'max:102400'], // 100MB
            'thumbnail_url' => ['nullable', 'string', 'max:1000'],
            'dimensions' => ['nullable', 'array'],
            'dimensions.width' => ['nullable', 'integer', 'min:1'],
            'dimensions.height' => ['nullable', 'integer', 'min:1'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'campaign_id' => ['nullable', 'string', 'exists:cmis.campaigns,campaign_id'],
            'tags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'asset_type.in' => 'Invalid asset type selected.',
            'file.max' => 'The file size must not exceed 100MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If file is uploaded, update mime_type and file_size
        if ($this->hasFile('file')) {
            $file = $this->file('file');
            $this->merge([
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}

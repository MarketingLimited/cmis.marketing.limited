<?php

namespace App\Http\Requests;

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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_type' => ['required', 'string', 'in:image,video,audio,document,template'],
            'file_url' => ['required_without:file', 'string', 'max:1000'],
            'file' => ['required_without:file_url', 'file', 'max:102400'], // 100MB
            'thumbnail_url' => ['nullable', 'string', 'max:1000'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
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
            'asset_name.required' => 'The asset name is required.',
            'asset_type.required' => 'Please select an asset type.',
            'asset_type.in' => 'Invalid asset type selected.',
            'file_url.required_without' => 'Either a file URL or file upload is required.',
            'file.required_without' => 'Either a file upload or file URL is required.',
            'file.max' => 'The file size must not exceed 100MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'org_id' => session('current_org_id'),
            'uploaded_by' => auth()->id(),
        ]);

        // If file is uploaded, set mime_type and file_size
        if ($this->hasFile('file')) {
            $file = $this->file('file');
            $this->merge([
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}

<?php

namespace App\Http\Requests\Creative;

use Illuminate\Foundation\Http\FormRequest;

class FilterCreativeAssetsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\CreativeAsset::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'asset_type' => ['sometimes', 'string', 'in:image,video,document,audio,other'],
            'status' => ['sometimes', 'string', 'in:pending_review,approved,rejected,archived'],
            'search' => ['sometimes', 'string', 'max:255'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'sort_by' => ['sometimes', 'string', 'in:created_at,updated_at,asset_name,file_size'],
            'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->per_page ?? 20,
            'page' => $this->page ?? 1,
            'sort_by' => $this->sort_by ?? 'created_at',
            'sort_direction' => $this->sort_direction ?? 'desc',
        ]);
    }
}

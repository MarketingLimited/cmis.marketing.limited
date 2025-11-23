<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Comment Request Validation
 *
 * Validates comment/reply creation
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:2000'],
            'parent_id' => ['nullable', 'uuid', 'exists:cmis.comments,comment_id'],
            'entity_type' => ['required', 'in:campaign,content,post,ad'],
            'entity_id' => ['required', 'uuid'],
            'mentions' => ['nullable', 'array'],
            'mentions.*' => ['uuid', 'exists:cmis.users,user_id'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'content.max' => 'Comment must not exceed 2000 characters',
            'entity_type.required' => 'Entity type is required',
            'entity_id.required' => 'Entity ID is required',
        ];
    }
}

<?php

namespace App\Http\Requests\BulkPost;

use Illuminate\Foundation\Http\FormRequest;

class CreateBulkPostsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // TODO: Add proper authorization check
        // return $this->user()->can('create-posts', $this->route('org_id'));
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
            'template' => 'required|array',
            'template.content' => 'required|string|max:5000',
            'template.platform' => 'nullable|string|in:facebook,instagram,twitter,linkedin,tiktok,snapchat',
            'template.post_type' => 'nullable|string|in:text,image,video,link,carousel',
            'template.media_urls' => 'nullable|array',
            'template.media_urls.*' => 'url',
            'template.hashtags' => 'nullable|array',
            'template.hashtags.*' => 'string|max:50',

            'accounts' => 'required|array|min:1|max:50',
            'accounts.*' => 'uuid',

            'options' => 'nullable|array',
            'options.auto_schedule' => 'nullable|boolean',
            'options.use_ai_variations' => 'nullable|boolean',
            'options.variation_style' => 'nullable|string|in:conservative,moderate,creative'
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'template.required' => 'Post template is required',
            'template.content.required' => 'Post content is required',
            'template.content.max' => 'Post content cannot exceed 5000 characters',
            'template.platform.in' => 'Invalid platform. Supported: facebook, instagram, twitter, linkedin, tiktok, snapchat',
            'template.post_type.in' => 'Invalid post type. Supported: text, image, video, link, carousel',
            'template.media_urls.*.url' => 'Each media URL must be a valid URL',
            'template.hashtags.*.max' => 'Each hashtag cannot exceed 50 characters',

            'accounts.required' => 'At least one social account is required',
            'accounts.min' => 'At least one social account is required',
            'accounts.max' => 'Cannot create posts for more than 50 accounts at once',
            'accounts.*.uuid' => 'Each account ID must be a valid UUID',

            'options.variation_style.in' => 'Invalid variation style. Supported: conservative, moderate, creative'
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'template.content' => 'post content',
            'template.platform' => 'platform',
            'template.post_type' => 'post type',
            'template.media_urls' => 'media URLs',
            'template.hashtags' => 'hashtags',
            'accounts' => 'social accounts',
            'options.auto_schedule' => 'auto-schedule option',
            'options.use_ai_variations' => 'AI variations option',
            'options.variation_style' => 'variation style'
        ];
    }
}

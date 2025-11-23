<?php

namespace App\Http\Requests\AdCreative;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Ad Creative Request Validation
 *
 * Validates ad creative/design creation
 */
class StoreAdCreativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'ad_format' => ['required', 'in:image,video,carousel,collection,story,reel'],
            'headline' => ['required', 'string', 'max:125'],
            'body_text' => ['nullable', 'string', 'max:500'],
            'call_to_action' => ['required', 'in:learn_more,shop_now,sign_up,download,contact_us,apply_now,book_now,get_quote'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'link_url' => ['required', 'url', 'max:500'],
            'display_link' => ['nullable', 'string', 'max:255'],
            'carousel_items' => ['nullable', 'array', 'max:10'],
            'carousel_items.*.image_url' => ['required_with:carousel_items', 'url'],
            'carousel_items.*.headline' => ['required_with:carousel_items', 'string', 'max:125'],
            'carousel_items.*.link_url' => ['required_with:carousel_items', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Creative name is required',
            'ad_format.required' => 'Ad format is required',
            'headline.required' => 'Headline is required',
            'headline.max' => 'Headline must not exceed 125 characters',
            'call_to_action.required' => 'Call to action is required',
            'link_url.required' => 'Destination URL is required',
            'carousel_items.max' => 'Maximum of 10 carousel items allowed',
        ];
    }
}

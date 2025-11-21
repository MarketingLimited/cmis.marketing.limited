<?php
namespace App\Validators;

class ContentValidator
{
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'platform' => 'required|in:facebook,instagram,twitter,linkedin',
            'status' => 'required|in:draft,pending,approved,published'
        ];
    }
}

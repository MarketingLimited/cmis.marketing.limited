<?php
namespace App\Validators;

class LeadValidator
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'source' => 'required|string'
        ];
    }
}

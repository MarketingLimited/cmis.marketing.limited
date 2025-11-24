<?php

return [
    // Common Validation
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'numeric' => 'The :attribute may not be greater than :max.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'numeric' => 'The :attribute must be a number.',
    'string' => 'The :attribute must be a string.',
    'url' => 'The :attribute format is invalid.',
    'date' => 'The :attribute is not a valid date.',
    'after' => 'The :attribute must be a date after :date.',
    'before' => 'The :attribute must be a date before :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'string' => 'The :attribute must be between :min and :max characters.',
    ],
    'in' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'image' => 'The :attribute must be an image.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'size' => [
        'file' => 'The :attribute must be :size kilobytes.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'array' => 'The :attribute must be an array.',

    // Custom Validation
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'integer' => 'The :attribute must be an integer.',
    'regex' => 'The :attribute format is invalid.',
    'same' => 'The :attribute and :other must match.',
    'timezone' => 'The :attribute must be a valid zone.',

    // Attribute Names
    'attributes' => [
        'name' => 'name',
        'email' => 'email address',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'first_name' => 'first name',
        'last_name' => 'last name',
        'phone' => 'phone number',
        'budget' => 'budget',
        'campaign_name' => 'campaign name',
        'start_date' => 'start date',
        'end_date' => 'end date',
    ],
];

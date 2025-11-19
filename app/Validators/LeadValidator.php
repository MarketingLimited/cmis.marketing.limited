<?php

namespace App\Validators;

class LeadValidator
{
    public function validate(array $data): array
    {
        // TODO: Implement lead validation logic
        return ['valid' => true];
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validatePhone(string $phone): bool
    {
        // TODO: Implement phone validation
        return !empty($phone);
    }
}

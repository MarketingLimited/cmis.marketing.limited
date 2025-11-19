<?php

namespace App\Services\Communication;

class EmailService
{
    public function __construct()
    {
        //
    }

    public function send(array $data): bool
    {
        // TODO: Implement email sending logic
        return false;
    }

    public function sendTemplate(string $template, array $data): bool
    {
        // TODO: Implement template email sending
        return false;
    }

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

<?php

namespace App\Validators;

class ContentValidator
{
    public function validate(array $data): array
    {
        // TODO: Implement content validation logic
        return ['valid' => true];
    }

    public function validateText(string $text): bool
    {
        // TODO: Implement text validation
        return !empty($text);
    }

    public function validateMedia(array $media): bool
    {
        // TODO: Implement media validation
        return true;
    }
}

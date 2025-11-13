<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class VectorCast implements CastsAttributes
{
    /**
     * Cast the given value from database to PHP array
     *
     * @param  array<string, mixed>  $attributes
     * @return array<float>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        // PostgreSQL vector format: [1,2,3,...]
        // Remove brackets and parse to float array
        $value = trim($value, '[]');

        if (empty($value)) {
            return null;
        }

        return array_map('floatval', explode(',', $value));
    }

    /**
     * Prepare the given value for storage in database
     *
     * @param  array<string, mixed>  $attributes
     * @return string|null
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            // Convert PHP array to PostgreSQL vector format
            return '[' . implode(',', array_map('floatval', $value)) . ']';
        }

        if (is_string($value)) {
            // Already in vector format
            return $value;
        }

        throw new \InvalidArgumentException('Vector value must be an array or string');
    }
}

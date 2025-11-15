<?php

namespace App\Services\CMIS\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Vector Validation Trait
 *
 * يوفر validation و error handling للـ Vector Integration Service
 */
trait HasVectorValidation
{
    /**
     * Validate string input
     *
     * @throws \InvalidArgumentException
     */
    protected function validateString(string $value, string $field, int $min = 1, int $max = 2000): void
    {
        if (empty($value)) {
            throw new \InvalidArgumentException("{$field} is required");
        }

        $length = mb_strlen($value);
        if ($length < $min) {
            throw new \InvalidArgumentException("{$field} must be at least {$min} characters (got {$length})");
        }

        if ($length > $max) {
            throw new \InvalidArgumentException("{$field} must not exceed {$max} characters (got {$length})");
        }
    }

    /**
     * Validate integer input
     *
     * @throws \InvalidArgumentException
     */
    protected function validateInt(int $value, string $field, int $min, int $max): void
    {
        if ($value < $min) {
            throw new \InvalidArgumentException("{$field} must be at least {$min} (got {$value})");
        }

        if ($value > $max) {
            throw new \InvalidArgumentException("{$field} must not exceed {$max} (got {$value})");
        }
    }

    /**
     * Validate array input
     *
     * @throws \InvalidArgumentException
     */
    protected function validateArray(array $value, string $field, int $maxItems = 100): void
    {
        if (count($value) > $maxItems) {
            throw new \InvalidArgumentException("{$field} must not contain more than {$maxItems} items");
        }
    }

    /**
     * Log operation with execution time
     */
    protected function logOperation(string $operation, array $context, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;

        Log::info("Vector operation: {$operation}", array_merge($context, [
            'execution_time_ms' => round($executionTime, 2),
            'timestamp' => now()->toIso8601String()
        ]));
    }

    /**
     * Log error with context
     */
    protected function logError(string $operation, \Exception $e, array $context = []): void
    {
        Log::error("Vector operation failed: {$operation}", array_merge($context, [
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toIso8601String()
        ]));
    }

    /**
     * Create cache key
     */
    protected function getCacheKey(string $prefix, ...$params): string
    {
        $serialized = implode('|', array_map(function ($param) {
            return is_array($param) ? json_encode($param) : (string)$param;
        }, $params));

        return "{$prefix}:" . md5($serialized);
    }

    /**
     * Handle database exception
     *
     * @throws \RuntimeException
     */
    protected function handleDatabaseException(\PDOException $e, string $operation): void
    {
        $this->logError($operation, $e);
        throw new \RuntimeException("Database error during {$operation}: " . $e->getMessage());
    }

    /**
     * Handle general exception
     */
    protected function handleGeneralException(\Exception $e, string $operation): void
    {
        $this->logError($operation, $e);

        // رفع الاستثناء الأصلي إذا كان InvalidArgumentException أو RuntimeException
        if ($e instanceof \InvalidArgumentException || $e instanceof \RuntimeException) {
            throw $e;
        }

        // وإلا نرفع استثناء عام
        throw new \RuntimeException("Error during {$operation}: " . $e->getMessage(), 0, $e);
    }
}

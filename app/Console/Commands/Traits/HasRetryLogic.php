<?php

namespace App\Console\Commands\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Retry Logic Trait for Transient Failures
 * Issue #50: Automatically retries transient failures
 *
 * Usage: use HasRetryLogic; in your command class
 */
trait HasRetryLogic
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 1; // seconds
    protected array $retryableExceptions = [
        \GuzzleHttp\Exception\ConnectException::class,
        \GuzzleHttp\Exception\ServerException::class,
        \Illuminate\Http\Client\ConnectionException::class,
    ];

    protected function withRetry(callable $callback, string $operationName = 'Operation', int $maxRetries = null)
    {
        $maxRetries = $maxRetries ?? $this->maxRetries;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempt++;

                if (!$this->isRetryableException($e) || $attempt >= $maxRetries) {
                    throw $e;
                }

                $delay = $this->calculateBackoff($attempt);
                $this->warn(sprintf(
                    '⚠️  %s failed (attempt %d/%d): %s',
                    $operationName,
                    $attempt,
                    $maxRetries,
                    $e->getMessage()
                ));
                $this->info("🔄 Retrying in {$delay} seconds...");

                sleep($delay);
            }
        }

        throw new \RuntimeException("{$operationName} failed after {$maxRetries} attempts");
    }

    protected function isRetryableException(\Exception $e): bool
    {
        foreach ($this->retryableExceptions as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }

        // Check HTTP status codes for retryable errors
        if (method_exists($e, 'getCode')) {
            $code = $e->getCode();
            // Retry on 503 (Service Unavailable), 429 (Too Many Requests), 408 (Request Timeout)
            if (in_array($code, [503, 429, 408, 502, 504])) {
                return true;
            }
        }

        return false;
    }

    protected function calculateBackoff(int $attempt): int
    {
        // Exponential backoff: 1s, 2s, 4s, 8s, etc.
        return $this->retryDelay * (2 ** ($attempt - 1));
    }

    protected function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }

    protected function addRetryableException(string $exceptionClass): void
    {
        if (!in_array($exceptionClass, $this->retryableExceptions)) {
            $this->retryableExceptions[] = $exceptionClass;
        }
    }
}

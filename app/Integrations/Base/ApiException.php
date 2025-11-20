<?php

namespace App\Integrations\Base;

/**
 * API Exception
 *
 * Custom exception class for API-related errors
 */
class ApiException extends \Exception
{
    protected array $responseData = [];
    protected ?string $errorType = null;
    protected ?int $rateLimitReset = null;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        array $responseData = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
        $this->extractMetadata($responseData);
    }

    /**
     * Extract metadata from response data
     *
     * @param array $data Response data
     */
    protected function extractMetadata(array $data): void
    {
        // Extract error type
        $this->errorType = $data['error']['type'] ?? null;

        // Extract rate limit reset time
        if (isset($data['error']['reset_time'])) {
            $this->rateLimitReset = $data['error']['reset_time'];
        } elseif (isset($data['x-rate-limit-reset'])) {
            $this->rateLimitReset = $data['x-rate-limit-reset'];
        }
    }

    /**
     * Get response data
     *
     * @return array Response data
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Get error type
     *
     * @return string|null Error type
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Get rate limit reset time
     *
     * @return int|null Reset timestamp
     */
    public function getRateLimitReset(): ?int
    {
        return $this->rateLimitReset;
    }

    /**
     * Check if error is rate limit error
     *
     * @return bool True if rate limit error
     */
    public function isRateLimitError(): bool
    {
        return $this->getCode() === 429 ||
               in_array($this->errorType, ['RateLimitException', 'TooManyRequests']);
    }

    /**
     * Check if error is authentication error
     *
     * @return bool True if auth error
     */
    public function isAuthenticationError(): bool
    {
        return $this->getCode() === 401 ||
               in_array($this->errorType, ['OAuthException', 'UnauthorizedException']);
    }

    /**
     * Check if error is retryable
     *
     * @return bool True if should retry
     */
    public function isRetryable(): bool
    {
        // 5xx errors are generally retryable
        if ($this->getCode() >= 500 && $this->getCode() < 600) {
            return true;
        }

        // Rate limit errors are retryable
        if ($this->isRateLimitError()) {
            return true;
        }

        // Network/timeout errors are retryable
        if ($this->getCode() === 0 || $this->getCode() === 408) {
            return true;
        }

        return false;
    }
}

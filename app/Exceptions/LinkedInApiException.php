<?php

namespace App\Exceptions;

use Exception;

/**
 * LinkedIn API Exception
 *
 * Thrown when LinkedIn Marketing API returns errors
 * Provides specific error handling for LinkedIn API responses
 */
class LinkedInApiException extends Exception
{
    protected array $responseData;
    protected ?int $httpStatus;

    /**
     * Create a new LinkedIn API exception
     *
     * @param string $message Error message
     * @param int $httpStatus HTTP status code from LinkedIn API
     * @param array $responseData Full response data from LinkedIn
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'LinkedIn API error',
        int $httpStatus = 0,
        array $responseData = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->httpStatus = $httpStatus;
        $this->responseData = $responseData;
    }

    /**
     * Get HTTP status code from LinkedIn API
     */
    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Get full response data from LinkedIn API
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Get LinkedIn error code if available
     */
    public function getLinkedInErrorCode(): ?string
    {
        return $this->responseData['serviceErrorCode'] ?? $this->responseData['error'] ?? null;
    }

    /**
     * Get LinkedIn error message
     */
    public function getLinkedInErrorMessage(): ?string
    {
        return $this->responseData['message'] ?? $this->responseData['error_description'] ?? null;
    }

    /**
     * Check if error is due to authentication/authorization
     */
    public function isAuthError(): bool
    {
        return in_array($this->httpStatus, [401, 403]) ||
               in_array($this->getLinkedInErrorCode(), ['UNAUTHORIZED', 'FORBIDDEN']);
    }

    /**
     * Check if error is due to rate limiting
     */
    public function isRateLimitError(): bool
    {
        return $this->httpStatus === 429 ||
               $this->getLinkedInErrorCode() === 'TOO_MANY_REQUESTS';
    }

    /**
     * Check if error is a validation error
     */
    public function isValidationError(): bool
    {
        return $this->httpStatus === 400 ||
               in_array($this->getLinkedInErrorCode(), ['INVALID_REQUEST', 'VALIDATION_ERROR']);
    }

    /**
     * Check if error might be temporary (should retry)
     */
    public function isTemporaryError(): bool
    {
        return in_array($this->httpStatus, [429, 500, 502, 503, 504]) ||
               $this->getLinkedInErrorCode() === 'INTERNAL_SERVER_ERROR';
    }

    /**
     * Convert exception to array for logging/API responses
     */
    public function toArray(): array
    {
        return [
            'error' => 'linkedin_api_error',
            'message' => $this->getMessage(),
            'http_status' => $this->httpStatus,
            'linkedin_error_code' => $this->getLinkedInErrorCode(),
            'linkedin_error_message' => $this->getLinkedInErrorMessage(),
            'is_auth_error' => $this->isAuthError(),
            'is_rate_limit' => $this->isRateLimitError(),
            'is_temporary' => $this->isTemporaryError(),
        ];
    }
}

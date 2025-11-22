<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Sanitize exceptions to prevent stack trace exposure in API responses.
 *
 * This middleware catches unhandled exceptions and ensures they are
 * returned in a safe, standardized format without exposing internal details.
 */
class SanitizeExceptions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            // Log full exception details server-side
            Log::error('Exception in request', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'org_id' => session('current_org_id'),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // For API requests, return sanitized JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->jsonErrorResponse($e);
            }

            // For web requests, rethrow to let Laravel handle it
            throw $e;
        }
    }

    /**
     * Create a sanitized JSON error response.
     */
    protected function jsonErrorResponse(Throwable $e): Response
    {
        // Determine status code
        $statusCode = $this->getStatusCode($e);

        // Determine error code
        $errorCode = $this->getErrorCode($e);

        // Determine safe message
        $message = $this->getSafeMessage($e, $statusCode);

        $response = [
            'success' => false,
            'message' => $message,
            'code' => $errorCode,
        ];

        // In debug mode, include more details (development only)
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get HTTP status code from exception.
     */
    protected function getStatusCode(Throwable $e): int
    {
        // Check if exception has getStatusCode method
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // Check if exception has a code that's a valid HTTP status
        $code = $e->getCode();
        if ($code >= 400 && $code < 600) {
            return $code;
        }

        // Default to 500 Internal Server Error
        return 500;
    }

    /**
     * Get machine-readable error code from exception.
     */
    protected function getErrorCode(Throwable $e): string
    {
        $className = class_basename($e);

        // Convert exception class name to error code
        // E.g., ValidationException -> VALIDATION_ERROR
        $errorCode = strtoupper(preg_replace('/Exception$/', '', $className));
        $errorCode = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $errorCode));

        return $errorCode ?: 'INTERNAL_ERROR';
    }

    /**
     * Get a safe error message that doesn't expose internals.
     */
    protected function getSafeMessage(Throwable $e, int $statusCode): string
    {
        // In production, never expose the actual exception message
        if (!config('app.debug')) {
            return match ($statusCode) {
                400 => 'Bad request. Please check your input and try again.',
                401 => 'Unauthorized. Please log in to access this resource.',
                403 => 'Forbidden. You do not have permission to access this resource.',
                404 => 'Resource not found.',
                422 => 'Validation failed. Please check your input.',
                429 => 'Too many requests. Please slow down and try again later.',
                500 => 'An unexpected error occurred. Please try again or contact support if the problem persists.',
                503 => 'Service temporarily unavailable. Please try again later.',
                default => 'An error occurred while processing your request.',
            };
        }

        // In debug mode, return the actual message
        return $e->getMessage();
    }
}

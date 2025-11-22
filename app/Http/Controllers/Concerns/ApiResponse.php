<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * ApiResponse Trait
 *
 * Provides standardized JSON response methods for API controllers.
 * Eliminates duplicate response patterns across 148+ controllers.
 *
 * Usage:
 * ```php
 * class MyController extends Controller
 * {
 *     use ApiResponse;
 *
 *     public function index()
 *     {
 *         return $this->success($data, 'Data retrieved successfully');
 *     }
 * }
 * ```
 *
 * @package App\Http\Controllers\Concerns
 */
trait ApiResponse
{
    /**
     * Return a success JSON response
     *
     * @param mixed $data The data to return
     * @param string $message Success message
     * @param int $code HTTP status code
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param mixed $errors Additional error details (optional)
     * @param string|null $errorCode Machine-readable error code (optional)
     * @return JsonResponse
     */
    protected function error(string $message = 'An error occurred', int $code = 400, $errors = null, ?string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $response['code'] = $errorCode;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a created resource response (201)
     *
     * @param mixed $data The created resource data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a deleted resource response (200)
     *
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Return a no content response (204)
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Return a not found response (404)
     *
     * @param string $message Error message
     * @param string|null $errorCode Machine-readable error code
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found', ?string $errorCode = 'RESOURCE_NOT_FOUND'): JsonResponse
    {
        return $this->error($message, 404, null, $errorCode);
    }

    /**
     * Return an unauthorized response (401)
     *
     * @param string $message Error message
     * @param string|null $errorCode Machine-readable error code
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized', ?string $errorCode = 'UNAUTHORIZED'): JsonResponse
    {
        return $this->error($message, 401, null, $errorCode);
    }

    /**
     * Return a forbidden response (403)
     *
     * @param string $message Error message
     * @param string|null $errorCode Machine-readable error code
     * @param string|null $requiredPermission Permission needed to access resource
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden', ?string $errorCode = 'FORBIDDEN', ?string $requiredPermission = null): JsonResponse
    {
        $additionalData = null;
        if ($requiredPermission) {
            $additionalData = ['required_permission' => $requiredPermission];
        }
        return $this->error($message, 403, $additionalData, $errorCode);
    }

    /**
     * Return a validation error response (422)
     *
     * @param mixed $errors Validation errors
     * @param string $message Error message
     * @return JsonResponse
     */
    protected function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return a server error response (500)
     *
     * @param string $message Error message
     * @param bool $logError Whether to log the error
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal server error', bool $logError = true): JsonResponse
    {
        // Never expose stack traces or sensitive error details in production
        if (!config('app.debug')) {
            $message = 'An unexpected error occurred. Please try again or contact support.';
        }

        if ($logError) {
            \Log::error('Server error: ' . $message);
        }

        return $this->error($message, 500, null, 'INTERNAL_ERROR');
    }

    /**
     * Return a paginated response
     *
     * @param mixed $paginator Laravel paginator instance
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function paginated($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }
}

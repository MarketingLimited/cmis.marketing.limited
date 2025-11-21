<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Trait ValidatesAnalyticsRequests (Phase 10)
 *
 * Standardizes request validation and error responses for analytics APIs
 *
 * Usage:
 * ```php
 * use ValidatesAnalyticsRequests;
 *
 * $validated = $this->validateAnalyticsRequest($request->all(), [
 *     'date_range' => 'required|array',
 *     'date_range.start' => 'required|date',
 *     'date_range.end' => 'required|date|after_or_equal:date_range.start'
 * ]);
 * ```
 */
trait ValidatesAnalyticsRequests
{
    /**
     * Validate analytics request data
     *
     * @param array $data Request data to validate
     * @param array $rules Validation rules
     * @param array $messages Custom error messages
     * @return array Validated data
     * @throws ValidationException
     */
    protected function validateAnalyticsRequest(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate date range
     *
     * @param array $dateRange Date range array with 'start' and 'end'
     * @return array Validated date range
     * @throws ValidationException
     */
    protected function validateDateRange(array $dateRange): array
    {
        return $this->validateAnalyticsRequest($dateRange, [
            'start' => 'required|date|before_or_equal:today',
            'end' => 'required|date|after_or_equal:start|before_or_equal:today'
        ], [
            'start.required' => 'Start date is required',
            'start.date' => 'Start date must be a valid date',
            'start.before_or_equal' => 'Start date cannot be in the future',
            'end.required' => 'End date is required',
            'end.date' => 'End date must be a valid date',
            'end.after_or_equal' => 'End date must be after or equal to start date',
            'end.before_or_equal' => 'End date cannot be in the future'
        ]);
    }

    /**
     * Validate time window parameter
     *
     * @param string $window Time window value
     * @return string Validated window
     * @throws ValidationException
     */
    protected function validateTimeWindow(string $window): string
    {
        $validated = $this->validateAnalyticsRequest(['window' => $window], [
            'window' => 'required|in:1m,5m,15m,1h,24h'
        ], [
            'window.required' => 'Time window is required',
            'window.in' => 'Time window must be one of: 1m, 5m, 15m, 1h, 24h'
        ]);

        return $validated['window'];
    }

    /**
     * Validate attribution model
     *
     * @param string $model Attribution model name
     * @return string Validated model
     * @throws ValidationException
     */
    protected function validateAttributionModel(string $model): string
    {
        $validated = $this->validateAnalyticsRequest(['model' => $model], [
            'model' => 'required|in:last-click,first-click,linear,time-decay,position-based,data-driven'
        ], [
            'model.required' => 'Attribution model is required',
            'model.in' => 'Invalid attribution model. Must be one of: last-click, first-click, linear, time-decay, position-based, data-driven'
        ]);

        return $validated['model'];
    }

    /**
     * Validate entity type and ID
     *
     * @param string $entityType Entity type (org, campaign, channel, etc.)
     * @param string $entityId Entity UUID
     * @return array Validated entity data
     * @throws ValidationException
     */
    protected function validateEntity(string $entityType, string $entityId): array
    {
        return $this->validateAnalyticsRequest([
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ], [
            'entity_type' => 'required|in:org,campaign,channel,platform',
            'entity_id' => 'required|uuid'
        ], [
            'entity_type.required' => 'Entity type is required',
            'entity_type.in' => 'Invalid entity type. Must be one of: org, campaign, channel, platform',
            'entity_id.required' => 'Entity ID is required',
            'entity_id.uuid' => 'Entity ID must be a valid UUID'
        ]);
    }

    /**
     * Return standardized success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $status HTTP status code
     * @return JsonResponse
     */
    protected function successResponse($data, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Return standardized error response
     *
     * @param string $error Error message
     * @param int $status HTTP status code
     * @param array $details Additional error details
     * @return JsonResponse
     */
    protected function errorResponse(string $error, int $status = 400, array $details = []): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => $error
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return response()->json($response, $status);
    }

    /**
     * Return validation error response
     *
     * @param ValidationException $exception Validation exception
     * @return JsonResponse
     */
    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $exception->errors()
        ], 422);
    }

    /**
     * Handle API exceptions and return appropriate response
     *
     * @param \Exception $exception Exception to handle
     * @return JsonResponse
     */
    protected function handleApiException(\Exception $exception): JsonResponse
    {
        // Log the exception
        \Log::error('Analytics API Error: ' . $exception->getMessage(), [
            'exception' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ]);

        // Return appropriate response based on exception type
        if ($exception instanceof ValidationException) {
            return $this->validationErrorResponse($exception);
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Resource not found', 404);
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->errorResponse('Unauthorized', 401);
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->errorResponse('Forbidden', 403);
        }

        // Generic error response
        return $this->errorResponse(
            config('app.debug') ? $exception->getMessage() : 'An error occurred',
            500
        );
    }

    /**
     * Validate pagination parameters
     *
     * @param array $params Parameters containing 'page' and 'limit'
     * @return array Validated pagination params
     * @throws ValidationException
     */
    protected function validatePagination(array $params): array
    {
        return $this->validateAnalyticsRequest($params, [
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100'
        ], [
            'page.integer' => 'Page must be an integer',
            'page.min' => 'Page must be at least 1',
            'limit.integer' => 'Limit must be an integer',
            'limit.min' => 'Limit must be at least 1',
            'limit.max' => 'Limit cannot exceed 100'
        ]);
    }

    /**
     * Validate severity filter
     *
     * @param string|null $severity Severity level
     * @return string|null Validated severity
     * @throws ValidationException
     */
    protected function validateSeverity(?string $severity): ?string
    {
        if ($severity === null || $severity === 'all') {
            return null;
        }

        $validated = $this->validateAnalyticsRequest(['severity' => $severity], [
            'severity' => 'in:critical,high,medium,low'
        ], [
            'severity.in' => 'Invalid severity. Must be one of: critical, high, medium, low'
        ]);

        return $validated['severity'];
    }

    /**
     * Validate alert status
     *
     * @param string $status Alert status
     * @return string Validated status
     * @throws ValidationException
     */
    protected function validateAlertStatus(string $status): string
    {
        $validated = $this->validateAnalyticsRequest(['status' => $status], [
            'status' => 'required|in:active,acknowledged,resolved'
        ], [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status. Must be one of: active, acknowledged, resolved'
        ]);

        return $validated['status'];
    }

    /**
     * Validate metric names
     *
     * @param array $metrics Array of metric names
     * @return array Validated metrics
     * @throws ValidationException
     */
    protected function validateMetrics(array $metrics): array
    {
        $allowedMetrics = [
            'impressions',
            'clicks',
            'conversions',
            'spend',
            'revenue',
            'ctr',
            'cpc',
            'cpa',
            'roas',
            'roi'
        ];

        return $this->validateAnalyticsRequest(['metrics' => $metrics], [
            'metrics' => 'required|array',
            'metrics.*' => 'in:' . implode(',', $allowedMetrics)
        ], [
            'metrics.required' => 'Metrics array is required',
            'metrics.array' => 'Metrics must be an array',
            'metrics.*.in' => 'Invalid metric name. Allowed metrics: ' . implode(', ', $allowedMetrics)
        ]);
    }
}

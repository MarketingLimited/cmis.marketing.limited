<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Trait for handling async job status checks and responses
 *
 * Provides common methods for controllers that dispatch async jobs
 * and need to return job status information to clients.
 */
trait HandlesAsyncJobs
{
    /**
     * Get job status from cache
     *
     * @param string $jobId
     * @return array|null
     */
    protected function getJobStatus(string $jobId): ?array
    {
        $cacheKey = "ai_job_status:{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Get job result from cache
     *
     * @param string $jobId
     * @return array|null
     */
    protected function getJobResult(string $jobId): ?array
    {
        $cacheKey = "ai_job_result:{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Get embedding job status from cache
     *
     * @param string $jobId
     * @return array|null
     */
    protected function getEmbeddingJobStatus(string $jobId): ?array
    {
        $cacheKey = "embedding_job_status:{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Get embedding job result from cache
     *
     * @param string $jobId
     * @return array|null
     */
    protected function getEmbeddingJobResult(string $jobId): ?array
    {
        $cacheKey = "embedding_job_result:{$jobId}";
        return Cache::get($cacheKey);
    }

    /**
     * Return async job accepted response
     *
     * @param string $jobId
     * @param string $message
     * @param array $additionalData
     * @return JsonResponse
     */
    protected function asyncJobAccepted(
        string $jobId,
        string $message = 'Job queued for processing',
        array $additionalData = []
    ): JsonResponse {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'job_id' => $jobId,
            'status' => 'queued',
            'status_url' => route('api.jobs.status', ['job_id' => $jobId]),
        ], $additionalData), 202); // 202 Accepted
    }

    /**
     * Return job status response
     *
     * @param string $jobId
     * @return JsonResponse
     */
    protected function jobStatusResponse(string $jobId): JsonResponse
    {
        $status = $this->getJobStatus($jobId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        $response = [
            'success' => true,
            'job_id' => $jobId,
            'status' => $status['status'],
            'updated_at' => $status['updated_at'],
        ];

        // If completed, include result
        if ($status['status'] === 'completed') {
            $result = $this->getJobResult($jobId);
            if ($result) {
                $response['result'] = $result;
            }
        }

        // If failed, include error
        if ($status['status'] === 'failed' && isset($status['error'])) {
            $response['error'] = $status['error'];
        }

        return response()->json($response);
    }

    /**
     * Return embedding job status response
     *
     * @param string $jobId
     * @return JsonResponse
     */
    protected function embeddingJobStatusResponse(string $jobId): JsonResponse
    {
        $status = $this->getEmbeddingJobStatus($jobId);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        $response = [
            'success' => true,
            'job_id' => $jobId,
            'status' => $status['status'],
            'entity_type' => $status['entity_type'] ?? null,
            'entity_id' => $status['entity_id'] ?? null,
            'updated_at' => $status['updated_at'],
        ];

        // If completed, include result
        if ($status['status'] === 'completed') {
            $result = $this->getEmbeddingJobResult($jobId);
            if ($result) {
                $response['result'] = $result;
            }
        }

        // If failed, include error
        if ($status['status'] === 'failed' && isset($status['error'])) {
            $response['error'] = $status['error'];
        }

        return response()->json($response);
    }

    /**
     * Check if request wants async processing
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $defaultAsync Default value if not specified
     * @return bool
     */
    protected function shouldProcessAsync($request, bool $defaultAsync = true): bool
    {
        return $request->boolean('async', $defaultAsync);
    }
}

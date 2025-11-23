<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Traits\HandlesAsyncJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Job Status Controller
 *
 * Provides endpoints for checking the status of asynchronous jobs
 * (AI generation, embedding processing, etc.)
 */
class JobStatusController extends Controller
{
    use HandlesAsyncJobs, ApiResponse;

    /**
     * Get status of an AI generation job
     *
     * GET /api/jobs/{job_id}/status
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function status(string $jobId): JsonResponse
    {
        return $this->jobStatusResponse($jobId);
    }

    /**
     * Get status of an embedding job
     *
     * GET /api/jobs/{job_id}/embedding-status
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function embeddingStatus(string $jobId): JsonResponse
    {
        return $this->embeddingJobStatusResponse($jobId);
    }

    /**
     * Get result of a completed job
     *
     * GET /api/jobs/{job_id}/result
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function result(string $jobId): JsonResponse
    {
        $result = $this->getJobResult($jobId);

        if (!$result) {
            return $this->notFound('Job result not found or job not completed');
        }

        return $this->success([
            'job_id' => $jobId,
            'result' => $result,
        ], 'Job result retrieved successfully');
    }

    /**
     * Get result of a completed embedding job
     *
     * GET /api/jobs/{job_id}/embedding-result
     *
     * @param string $jobId
     * @return JsonResponse
     */
    public function embeddingResult(string $jobId): JsonResponse
    {
        $result = $this->getEmbeddingJobResult($jobId);

        if (!$result) {
            return $this->notFound('Job result not found or job not completed');
        }

        return $this->success([
            'job_id' => $jobId,
            'result' => $result,
        ], 'Embedding job result retrieved successfully');
    }
}

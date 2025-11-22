<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job Status Service
 *
 * Makes background job status visible to users.
 * Fixes Critical Issue #81: Queue failures not visible to users
 */
class JobStatusService
{
    /**
     * Record job start
     */
    public function recordJobStart(string $jobId, string $jobType, string $userId, ?string $orgId = null, array $metadata = []): void
    {
        DB::table('cmis_operations.job_status')->insert([
            'id' => $jobId,
            'job_type' => $jobType,
            'user_id' => $userId,
            'org_id' => $orgId,
            'status' => 'processing',
            'progress_percentage' => 0,
            'metadata' => json_encode($metadata),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Update job progress
     */
    public function updateProgress(string $jobId, int $percentage, ?string $message = null): void
    {
        DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->update([
                'progress_percentage' => min(100, max(0, $percentage)),
                'progress_message' => $message,
                'updated_at' => now(),
            ]);
    }

    /**
     * Mark job as completed
     */
    public function markCompleted(string $jobId, $result = null): void
    {
        DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'progress_percentage' => 100,
                'result' => is_array($result) ? json_encode($result) : $result,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('Job completed successfully', ['job_id' => $jobId]);
    }

    /**
     * Mark job as failed
     */
    public function markFailed(string $jobId, string $errorMessage, ?\Throwable $exception = null): void
    {
        $errorDetails = [
            'message' => $errorMessage,
            'exception_class' => $exception ? get_class($exception) : null,
            'exception_message' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ];

        DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'error_details' => json_encode($errorDetails),
                'failed_at' => now(),
                'updated_at' => now(),
            ]);

        Log::error('Job failed', [
            'job_id' => $jobId,
            'error' => $errorMessage,
            'exception' => $exception?->getMessage()
        ]);

        // Notify user
        $this->notifyUserOfFailure($jobId, $errorMessage);
    }

    /**
     * Get job status
     */
    public function getStatus(string $jobId): ?array
    {
        $job = DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->first();

        if (!$job) {
            return null;
        }

        return [
            'id' => $job->id,
            'type' => $job->job_type,
            'status' => $job->status,
            'progress' => $job->progress_percentage,
            'progress_message' => $job->progress_message,
            'started_at' => $job->started_at,
            'completed_at' => $job->completed_at,
            'failed_at' => $job->failed_at,
            'error_message' => $job->error_message,
            'can_retry' => $job->status === 'failed' && $this->isRetryable($job->job_type),
        ];
    }

    /**
     * Get user's recent jobs
     */
    public function getUserJobs(string $userId, int $limit = 20): array
    {
        $jobs = DB::table('cmis_operations.job_status')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'type' => $job->job_type,
                'status' => $job->status,
                'progress' => $job->progress_percentage ?? 0,
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
                'failed_at' => $job->failed_at,
                'error_message' => $job->error_message,
            ];
        })->toArray();
    }

    /**
     * Get failed jobs count for user
     */
    public function getFailedJobsCount(string $userId): int
    {
        return DB::table('cmis_operations.job_status')
            ->where('user_id', $userId)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
    }

    /**
     * Retry a failed job
     */
    public function retryJob(string $jobId): bool
    {
        $job = DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->first();

        if (!$job || $job->status !== 'failed') {
            return false;
        }

        if (!$this->isRetryable($job->job_type)) {
            return false;
        }

        // Reset job status
        DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'error_details' => null,
                'failed_at' => null,
                'updated_at' => now(),
            ]);

        // Re-dispatch job
        $this->redispatchJob($job);

        return true;
    }

    /**
     * Check if job type is retryable
     */
    protected function isRetryable(string $jobType): bool
    {
        $retryableJobs = [
            'embedding_generation',
            'report_generation',
            'platform_sync',
            'bulk_operation',
        ];

        return in_array($jobType, $retryableJobs);
    }

    /**
     * Re-dispatch a failed job
     */
    protected function redispatchJob($job): void
    {
        // TODO: Implement job re-dispatch based on job type
        Log::info('Re-dispatching job', ['job_id' => $job->id, 'type' => $job->job_type]);
    }

    /**
     * Notify user of job failure
     */
    protected function notifyUserOfFailure(string $jobId, string $errorMessage): void
    {
        // TODO: Implement user notification (in-app notification, email, etc.)
        Log::info('User should be notified of job failure', [
            'job_id' => $jobId,
            'error' => $errorMessage
        ]);
    }
}

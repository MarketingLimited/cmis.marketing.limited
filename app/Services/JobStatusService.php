<?php

namespace App\Services;

use App\Jobs\AI\GenerateEmbeddingsJob;
use App\Jobs\Analytics\GenerateReportJob;
use App\Jobs\Platform\SyncPlatformDataJob;
use App\Jobs\Bulk\ProcessBulkOperationJob;
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
    protected NotificationService $notificationService;

    public function __construct(?NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }

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
        Log::info('Re-dispatching job', ['job_id' => $job->id, 'type' => $job->job_type]);

        $metadata = json_decode($job->metadata ?? '{}', true);

        try {
            match ($job->job_type) {
                'embedding_generation' => $this->redispatchEmbeddingJob($job, $metadata),
                'report_generation' => $this->redispatchReportJob($job, $metadata),
                'platform_sync' => $this->redispatchPlatformSyncJob($job, $metadata),
                'bulk_operation' => $this->redispatchBulkOperationJob($job, $metadata),
                default => $this->handleUnknownJobType($job),
            };

            // Update job status to retrying
            DB::table('cmis_operations.job_status')
                ->where('id', $job->id)
                ->update([
                    'status' => 'retrying',
                    'retry_count' => DB::raw('COALESCE(retry_count, 0) + 1'),
                    'last_retry_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::info('Job re-dispatched successfully', [
                'job_id' => $job->id,
                'type' => $job->job_type,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to re-dispatch job', [
                'job_id' => $job->id,
                'type' => $job->job_type,
                'error' => $e->getMessage(),
            ]);

            // Mark as failed again with new error
            DB::table('cmis_operations.job_status')
                ->where('id', $job->id)
                ->update([
                    'status' => 'failed',
                    'error_message' => 'Re-dispatch failed: ' . $e->getMessage(),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Re-dispatch an embedding generation job
     */
    protected function redispatchEmbeddingJob($job, array $metadata): void
    {
        $entityType = $metadata['entity_type'] ?? null;
        $entityId = $metadata['entity_id'] ?? null;

        if (!$entityType || !$entityId) {
            throw new \Exception('Missing entity_type or entity_id in job metadata');
        }

        if (class_exists(GenerateEmbeddingsJob::class)) {
            GenerateEmbeddingsJob::dispatch(
                $entityType,
                $entityId,
                $job->org_id,
                $job->user_id
            )->onQueue('embeddings');
        } else {
            Log::warning('GenerateEmbeddingsJob class not found, skipping dispatch', [
                'job_id' => $job->id,
            ]);
        }
    }

    /**
     * Re-dispatch a report generation job
     */
    protected function redispatchReportJob($job, array $metadata): void
    {
        $reportType = $metadata['report_type'] ?? null;
        $reportConfig = $metadata['config'] ?? [];

        if (!$reportType) {
            throw new \Exception('Missing report_type in job metadata');
        }

        if (class_exists(GenerateReportJob::class)) {
            GenerateReportJob::dispatch(
                $reportType,
                $reportConfig,
                $job->org_id,
                $job->user_id
            )->onQueue('reports');
        } else {
            Log::warning('GenerateReportJob class not found, skipping dispatch', [
                'job_id' => $job->id,
            ]);
        }
    }

    /**
     * Re-dispatch a platform sync job
     */
    protected function redispatchPlatformSyncJob($job, array $metadata): void
    {
        $integrationId = $metadata['integration_id'] ?? null;
        $syncType = $metadata['sync_type'] ?? 'full';

        if (!$integrationId) {
            throw new \Exception('Missing integration_id in job metadata');
        }

        if (class_exists(SyncPlatformDataJob::class)) {
            SyncPlatformDataJob::dispatch(
                $integrationId,
                $syncType,
                $job->org_id,
                $job->user_id
            )->onQueue('platform-sync');
        } else {
            Log::warning('SyncPlatformDataJob class not found, skipping dispatch', [
                'job_id' => $job->id,
            ]);
        }
    }

    /**
     * Re-dispatch a bulk operation job
     */
    protected function redispatchBulkOperationJob($job, array $metadata): void
    {
        $operationType = $metadata['operation_type'] ?? null;
        $operationData = $metadata['operation_data'] ?? [];

        if (!$operationType) {
            throw new \Exception('Missing operation_type in job metadata');
        }

        if (class_exists(ProcessBulkOperationJob::class)) {
            ProcessBulkOperationJob::dispatch(
                $operationType,
                $operationData,
                $job->org_id,
                $job->user_id
            )->onQueue('bulk-operations');
        } else {
            Log::warning('ProcessBulkOperationJob class not found, skipping dispatch', [
                'job_id' => $job->id,
            ]);
        }
    }

    /**
     * Handle unknown job type
     */
    protected function handleUnknownJobType($job): void
    {
        Log::warning('Cannot re-dispatch unknown job type', [
            'job_id' => $job->id,
            'type' => $job->job_type,
        ]);

        throw new \Exception("Unknown job type: {$job->job_type}");
    }

    /**
     * Notify user of job failure
     */
    protected function notifyUserOfFailure(string $jobId, string $errorMessage): void
    {
        $job = DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->first();

        if (!$job || !$job->user_id) {
            Log::warning('Cannot notify user - job or user_id not found', ['job_id' => $jobId]);
            return;
        }

        try {
            $this->notificationService->notifyJobFailure(
                $job->user_id,
                $jobId,
                $job->job_type,
                $errorMessage,
                ['org_id' => $job->org_id]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send job failure notification', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify user of job completion
     */
    public function notifyUserOfCompletion(string $jobId): void
    {
        $job = DB::table('cmis_operations.job_status')
            ->where('id', $jobId)
            ->first();

        if (!$job || !$job->user_id) {
            return;
        }

        try {
            $this->notificationService->notifyJobCompletion(
                $job->user_id,
                $jobId,
                $job->job_type,
                $job->result ? json_decode($job->result, true) : null,
                ['org_id' => $job->org_id]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to send job completion notification', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

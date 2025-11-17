<?php

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for post approval workflows (Creator → Reviewer → Publisher)
 * Implements Sprint 2.4: Approval Workflow
 *
 * Features:
 * - Multi-step approval process
 * - Role-based assignment (reviewers, publishers)
 * - Approval history tracking
 * - Automatic notifications
 * - Reassignment capability
 * - Comments and feedback
 */
class ApprovalWorkflowService
{
    protected NotificationRepositoryInterface $notificationRepo;

    public function __construct(NotificationRepositoryInterface $notificationRepo)
    {
        $this->notificationRepo = $notificationRepo;
    }

    /**
     * Request approval for post
     *
     * @param string $postId
     * @param string $requestedBy User who created the post
     * @param string|null $assignedTo Reviewer to assign (null = any reviewer)
     * @return object
     */
    public function requestApproval(string $postId, string $requestedBy, ?string $assignedTo = null): object
    {
        try {
            DB::beginTransaction();

            // Create approval request
            $approvalId = Str::uuid()->toString();

            DB::table('cmis.post_approvals')->insert([
                'approval_id' => $approvalId,
                'post_id' => $postId,
                'requested_by' => $requestedBy,
                'assigned_to' => $assignedTo,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update post status
            DB::table('cmis.social_posts')
                ->where('post_id', $postId)
                ->update([
                    'status' => 'pending_approval',
                    'updated_at' => now()
                ]);

            // Send notification to reviewer
            if ($assignedTo) {
                $this->sendNotification($assignedTo, 'approval_requested', [
                    'post_id' => $postId,
                    'approval_id' => $approvalId,
                    'requested_by' => $requestedBy
                ]);
            }

            DB::commit();

            Log::info('Approval requested', [
                'approval_id' => $approvalId,
                'post_id' => $postId,
                'requested_by' => $requestedBy,
                'assigned_to' => $assignedTo
            ]);

            return (object) [
                'approval_id' => $approvalId,
                'post_id' => $postId,
                'status' => 'pending',
                'assigned_to' => $assignedTo,
                'created_at' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to request approval', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Approve post
     *
     * @param string $approvalId
     * @param string $approvedBy User approving
     * @param string|null $comments Optional approval comments
     * @return bool
     */
    public function approve(string $approvalId, string $approvedBy, ?string $comments = null): bool
    {
        try {
            DB::beginTransaction();

            // Get approval record
            $approval = DB::table('cmis.post_approvals')
                ->where('approval_id', $approvalId)
                ->first();

            if (!$approval) {
                throw new \Exception('Approval not found');
            }

            if ($approval->status !== 'pending') {
                throw new \Exception('Approval already processed');
            }

            // Update approval
            DB::table('cmis.post_approvals')
                ->where('approval_id', $approvalId)
                ->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'comments' => $comments,
                    'updated_at' => now()
                ]);

            // Update post status
            DB::table('cmis.social_posts')
                ->where('post_id', $approval->post_id)
                ->update([
                    'status' => 'approved',
                    'updated_at' => now()
                ]);

            // Notify creator
            $this->sendNotification($approval->requested_by, 'approval_approved', [
                'post_id' => $approval->post_id,
                'approval_id' => $approvalId,
                'approved_by' => $approvedBy,
                'comments' => $comments
            ]);

            DB::commit();

            Log::info('Post approved', [
                'approval_id' => $approvalId,
                'post_id' => $approval->post_id,
                'approved_by' => $approvedBy
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve post', [
                'approval_id' => $approvalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reject post with feedback
     *
     * @param string $approvalId
     * @param string $rejectedBy User rejecting
     * @param string $comments Required rejection reason
     * @return bool
     */
    public function reject(string $approvalId, string $rejectedBy, string $comments): bool
    {
        try {
            DB::beginTransaction();

            // Get approval record
            $approval = DB::table('cmis.post_approvals')
                ->where('approval_id', $approvalId)
                ->first();

            if (!$approval) {
                throw new \Exception('Approval not found');
            }

            if ($approval->status !== 'pending') {
                throw new \Exception('Approval already processed');
            }

            // Update approval
            DB::table('cmis.post_approvals')
                ->where('approval_id', $approvalId)
                ->update([
                    'status' => 'rejected',
                    'reviewed_at' => now(),
                    'comments' => $comments,
                    'updated_at' => now()
                ]);

            // Update post status back to draft
            DB::table('cmis.social_posts')
                ->where('post_id', $approval->post_id)
                ->update([
                    'status' => 'draft',
                    'updated_at' => now()
                ]);

            // Notify creator with feedback
            $this->sendNotification($approval->requested_by, 'approval_rejected', [
                'post_id' => $approval->post_id,
                'approval_id' => $approvalId,
                'rejected_by' => $rejectedBy,
                'comments' => $comments
            ]);

            DB::commit();

            Log::info('Post rejected', [
                'approval_id' => $approvalId,
                'post_id' => $approval->post_id,
                'rejected_by' => $rejectedBy
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject post', [
                'approval_id' => $approvalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reassign approval to different reviewer
     *
     * @param string $approvalId
     * @param string $newAssignee
     * @return bool
     */
    public function reassign(string $approvalId, string $newAssignee): bool
    {
        try {
            $updated = DB::table('cmis.post_approvals')
                ->where('approval_id', $approvalId)
                ->where('status', 'pending')
                ->update([
                    'assigned_to' => $newAssignee,
                    'updated_at' => now()
                ]);

            if ($updated) {
                // Notify new assignee
                $approval = DB::table('cmis.post_approvals')
                    ->where('approval_id', $approvalId)
                    ->first();

                $this->sendNotification($newAssignee, 'approval_reassigned', [
                    'post_id' => $approval->post_id,
                    'approval_id' => $approvalId
                ]);

                Log::info('Approval reassigned', [
                    'approval_id' => $approvalId,
                    'new_assignee' => $newAssignee
                ]);
            }

            return $updated > 0;

        } catch (\Exception $e) {
            Log::error('Failed to reassign approval', [
                'approval_id' => $approvalId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get pending approvals for user
     *
     * @param string $userId
     * @param string|null $orgId Filter by organization
     * @return Collection
     */
    public function getPendingApprovals(string $userId, ?string $orgId = null): Collection
    {
        try {
            $query = DB::table('cmis.post_approvals as pa')
                ->join('cmis.social_posts as sp', 'pa.post_id', '=', 'sp.post_id')
                ->where('pa.status', 'pending')
                ->where(function ($q) use ($userId) {
                    $q->where('pa.assigned_to', $userId)
                      ->orWhereNull('pa.assigned_to'); // Unassigned approvals
                })
                ->select(
                    'pa.approval_id',
                    'pa.post_id',
                    'pa.requested_by',
                    'pa.assigned_to',
                    'pa.created_at',
                    'sp.content',
                    'sp.platform',
                    'sp.scheduled_for',
                    'sp.org_id'
                );

            if ($orgId) {
                $query->where('sp.org_id', $orgId);
            }

            return collect($query->orderBy('pa.created_at', 'asc')->get());

        } catch (\Exception $e) {
            Log::error('Failed to get pending approvals', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get approval history for post
     *
     * @param string $postId
     * @return Collection
     */
    public function getApprovalHistory(string $postId): Collection
    {
        try {
            $approvals = DB::table('cmis.post_approvals')
                ->where('post_id', $postId)
                ->orderBy('created_at', 'desc')
                ->get();

            return collect($approvals);

        } catch (\Exception $e) {
            Log::error('Failed to get approval history', [
                'post_id' => $postId,
                'error' => $e->getMessage()
            ]);
            return collect([]);
        }
    }

    /**
     * Get approval statistics for organization
     *
     * @param string $orgId
     * @param array $dateRange
     * @return array
     */
    public function getApprovalStats(string $orgId, array $dateRange = []): array
    {
        try {
            $query = DB::table('cmis.post_approvals as pa')
                ->join('cmis.social_posts as sp', 'pa.post_id', '=', 'sp.post_id')
                ->where('sp.org_id', $orgId);

            if (!empty($dateRange)) {
                $query->whereBetween('pa.created_at', [$dateRange['start'], $dateRange['end']]);
            }

            $total = $query->count();
            $pending = (clone $query)->where('pa.status', 'pending')->count();
            $approved = (clone $query)->where('pa.status', 'approved')->count();
            $rejected = (clone $query)->where('pa.status', 'rejected')->count();

            // Calculate average approval time
            $avgTime = DB::table('cmis.post_approvals as pa')
                ->join('cmis.social_posts as sp', 'pa.post_id', '=', 'sp.post_id')
                ->where('sp.org_id', $orgId)
                ->whereIn('pa.status', ['approved', 'rejected'])
                ->whereNotNull('pa.reviewed_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (pa.reviewed_at - pa.created_at))/3600) as avg_hours')
                ->first();

            return [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
                'avg_approval_time_hours' => round($avgTime->avg_hours ?? 0, 1)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get approval stats', [
                'org_id' => $orgId,
                'error' => $e->getMessage()
            ]);
            return [
                'total' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'approval_rate' => 0,
                'avg_approval_time_hours' => 0
            ];
        }
    }

    /**
     * Send notification to user
     *
     * @param string $userId
     * @param string $type
     * @param array $data
     * @return void
     */
    protected function sendNotification(string $userId, string $type, array $data): void
    {
        try {
            $title = $data['title'] ?? __('إشعار الموافقات');
            $message = $data['message'] ?? __('تم تحديث حالة طلب الموافقة الخاصة بك');

            $notificationId = $this->notificationRepo->createNotification(
                $userId,
                $type,
                $title,
                $message,
                $data
            );

            Log::info('Approval workflow notification dispatched', [
                'user_id' => $userId,
                'type' => $type,
                'notification_id' => $notificationId,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to send notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }
}

<?php

namespace App\Models\Workflow;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ApprovalWorkflow Model
 *
 * Represents a multi-step approval workflow for content review and publishing.
 * Defines triggers, approval chains, and notification settings.
 *
 * @property string $workflow_id
 * @property string $org_id
 * @property string $profile_group_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property array $apply_to_platforms
 * @property array $apply_to_users
 * @property array $apply_to_post_types
 * @property array $approval_steps
 * @property bool $notify_on_submission
 * @property bool $notify_on_approval
 * @property bool $notify_on_rejection
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ApprovalWorkflow extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.approval_workflows';
    protected $primaryKey = 'workflow_id';

    protected $fillable = [
        'org_id',
        'profile_group_id',
        'name',
        'description',
        'is_active',
        'apply_to_platforms',
        'apply_to_users',
        'apply_to_post_types',
        'approval_steps',
        'notify_on_submission',
        'notify_on_approval',
        'notify_on_rejection',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'apply_to_platforms' => 'array',
        'apply_to_users' => 'array',
        'apply_to_post_types' => 'array',
        'approval_steps' => 'array',
        'notify_on_submission' => 'boolean',
        'notify_on_approval' => 'boolean',
        'notify_on_rejection' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the profile group this workflow belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who created this workflow
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope to get only active workflows
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get workflows for a specific platform
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereJsonContains('apply_to_platforms', $platform);
    }

    /**
     * Scope to get workflows for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->whereJsonContains('apply_to_users', $userId);
    }

    /**
     * Scope to get workflows for a specific post type
     */
    public function scopeForPostType($query, string $postType)
    {
        return $query->whereJsonContains('apply_to_post_types', $postType);
    }

    /**
     * Check if workflow applies to a specific platform
     */
    public function appliesToPlatform(string $platform): bool
    {
        // Empty array means applies to all platforms
        if (empty($this->apply_to_platforms)) {
            return true;
        }

        return in_array($platform, $this->apply_to_platforms);
    }

    /**
     * Check if workflow applies to a specific user
     */
    public function appliesToUser(string $userId): bool
    {
        // Empty array means applies to all users
        if (empty($this->apply_to_users)) {
            return true;
        }

        return in_array($userId, $this->apply_to_users);
    }

    /**
     * Check if workflow applies to a specific post type
     */
    public function appliesToPostType(string $postType): bool
    {
        // Empty array means applies to all post types
        if (empty($this->apply_to_post_types)) {
            return true;
        }

        return in_array($postType, $this->apply_to_post_types);
    }

    /**
     * Get the number of approval steps
     */
    public function getStepsCountAttribute(): int
    {
        return count($this->approval_steps ?? []);
    }

    /**
     * Get approval step by index
     */
    public function getStep(int $stepIndex): ?array
    {
        return $this->approval_steps[$stepIndex] ?? null;
    }

    /**
     * Get all approver user IDs from all steps
     */
    public function getAllApprovers(): array
    {
        $approvers = [];

        foreach ($this->approval_steps ?? [] as $step) {
            if (isset($step['approvers']) && is_array($step['approvers'])) {
                $approvers = array_merge($approvers, $step['approvers']);
            }
        }

        return array_unique($approvers);
    }

    /**
     * Check if user is an approver in any step
     */
    public function isApprover(string $userId): bool
    {
        return in_array($userId, $this->getAllApprovers());
    }

    /**
     * Check if user is an approver in a specific step
     */
    public function isApproverInStep(string $userId, int $stepIndex): bool
    {
        $step = $this->getStep($stepIndex);

        if (!$step || !isset($step['approvers'])) {
            return false;
        }

        return in_array($userId, $step['approvers']);
    }

    /**
     * Check if workflow requires all approvers in a step
     */
    public function requiresAllApproversInStep(int $stepIndex): bool
    {
        $step = $this->getStep($stepIndex);

        if (!$step) {
            return false;
        }

        return ($step['require_all'] ?? false) === true;
    }

    /**
     * Check if any notifications are enabled
     */
    public function hasNotificationsEnabled(): bool
    {
        return $this->notify_on_submission
            || $this->notify_on_approval
            || $this->notify_on_rejection;
    }

    /**
     * Check if workflow applies to a given context
     *
     * @param array $context ['platform' => 'meta', 'user_id' => '...', 'post_type' => 'feed']
     * @return bool
     */
    public function appliesTo(array $context): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check platform
        if (isset($context['platform']) && !$this->appliesToPlatform($context['platform'])) {
            return false;
        }

        // Check user
        if (isset($context['user_id']) && !$this->appliesToUser($context['user_id'])) {
            return false;
        }

        // Check post type
        if (isset($context['post_type']) && !$this->appliesToPostType($context['post_type'])) {
            return false;
        }

        return true;
    }
}

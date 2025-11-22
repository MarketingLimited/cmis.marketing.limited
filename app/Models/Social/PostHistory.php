<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostHistory extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.social_post_history';
    public $timestamps = false;

    protected $fillable = [
        'post_id', 'org_id', 'action', 'user_id',
        'old_status', 'new_status', 'changes', 'notes',
    ];

    protected $casts = [
        'changes' => 'array',
        'created_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // ===== Helper Methods =====

    /**
     * Create a history record for a post action
     */
    public static function logAction(
        string $postId,
        string $orgId,
        string $action,
        ?string $userId = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?array $changes = null,
        ?string $notes = null
    ): self {
        return self::create([
            'post_id' => $postId,
            'org_id' => $orgId,
            'action' => $action,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changes' => $changes,
            'notes' => $notes,
        ]);
    }
}

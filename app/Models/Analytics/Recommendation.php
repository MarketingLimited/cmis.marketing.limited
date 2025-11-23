<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recommendation extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.recommendations';
    protected $primaryKey = 'recommendation_id';
    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'recommendation_type', 'category',
        'priority', 'confidence_score', 'potential_impact', 'impact_metric',
        'title', 'description', 'action_details', 'supporting_data', 'status',
        'actioned_by', 'actioned_at', 'action_notes', 'expires_at'
    ];

    protected $casts = [
        'confidence_score' => 'decimal:2',
        'potential_impact' => 'decimal:2',
        'action_details' => 'array',
        'supporting_data' => 'array',
        'actioned_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];



    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by', 'user_id');
    }

    public function accept(string $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'accepted',
            'actioned_by' => $userId,
            'actioned_at' => now(),
            'action_notes' => $notes
        ]);
    }

    public function reject(string $userId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'actioned_by' => $userId,
            'actioned_at' => now(),
            'action_notes' => $reason
        ]);
    }

    public function implement(string $userId, string $notes): void
    {
        $this->update([
            'status' => 'implemented',
            'actioned_by' => $userId,
            'actioned_at' => now(),
            'action_notes' => $notes
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopePending($query): Builder
    {
        return $query->where('status', 'pending')
                     ->where(function($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeHighPriority($query): Builder
    {
        return $query->whereIn('priority', ['critical', 'high']);
    }

    public function scopeByCategory($query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}

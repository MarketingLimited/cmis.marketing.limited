<?php

namespace App\Models\Operations;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserActivity extends BaseModel
{
    use HasOrganization;
/**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    }
    /**
     * Log user activity.
     *
     * @param string $activityType
     * @param string|null $entityType
     * @param string|null $entityId
     * @param string|null $description
     * @param array|null $metadata
     * @return static
     */
    public static function log(
        string $activityType,
        ?string $entityType = null,
        ?string $entityId = null,
        ?string $description = null,
        ?array $metadata = null
    ): static {
        return static::create([
            'activity_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => session('current_org_id'),
            'user_id' => auth()->id(),
            'activity_type' => $activityType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'metadata' => $metadata,
        ]);

    }
    /**
     * Scope activities by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('activity_type', $type);

    }
    /**
     * Scope activities by entity.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $entityType
     * @param string|null $entityId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEntity($query, string $entityType, ?string $entityId = null): Builder
    {
        $query = $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);

        return $query;
}

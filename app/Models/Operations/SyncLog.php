<?php

namespace App\Models\Operations;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Integration;
use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SyncLog extends BaseModel
{
    use HasOrganization;
}
/**
     * Get the integration that this sync log belongs to.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'integration_id');

    }
    /**
     * Start a new sync log.
     *
     * @param string $orgId
     * @param string $integrationId
     * @param string $syncType
     * @param array|null $metadata
     * @return static
     */
    public static function start(
        string $orgId,
        string $integrationId,
        string $syncType,
        ?array $metadata = null
    ): static {
        return static::create([
            'sync_log_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $orgId,
            'integration_id' => $integrationId,
            'sync_type' => $syncType,
            'status' => 'in_progress',
            'started_at' => now(),
            'records_fetched' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_failed' => 0,
            'metadata' => $metadata,
        ]);

    }
    /**
     * Mark the sync as completed successfully.
     *
     * @param int $fetched
     * @param int $created
     * @param int $updated
     * @param int $failed
     * @return bool
     */
    public function complete(int $fetched, int $created, int $updated, int $failed = 0): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'records_fetched' => $fetched,
            'records_created' => $created,
            'records_updated' => $updated,
            'records_failed' => $failed,
        ]);

    }
    /**
     * Mark the sync as failed.
     *
     * @param string $errorMessage
     * @return bool
     */
    public function fail(string $errorMessage): bool
    {
        return $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);

    }
    /**
     * Scope successful syncs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query): Builder
    {
        return $query->where('status', 'completed')->where('records_failed', 0);

    }
    /**
     * Scope failed syncs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query): Builder
    {
        return $query->where('status', 'failed');

    }
    /**
     * Scope syncs by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('sync_type', $type);

    }
    /**
     * Get the duration of the sync in seconds.
     *
     * @return int|null
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;

        return $this->completed_at->diffInSeconds($this->started_at);
}
}

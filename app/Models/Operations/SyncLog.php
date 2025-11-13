<?php

namespace App\Models\Operations;

use App\Models\Core\Integration;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.sync_logs';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'platform',
        'synced_at',
        'status',
        'items',
        'level_counts',
        'provider',
    ];

    protected $casts = [
        'level_counts' => 'array',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sync_log_id' => 'string',
            'org_id' => 'string',
            'integration_id' => 'string',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'records_fetched' => 'integer',
            'records_created' => 'integer',
            'records_updated' => 'integer',
            'records_failed' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the organization that owns the sync log.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
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
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed')->where('records_failed', 0);
    }

    /**
     * Scope failed syncs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
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
    public function scopeByType($query, string $type)
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
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }
}

<?php

namespace App\Models\Operations;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserActivity extends Model
{
    use HasUuids;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.user_activities';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'activity_id';

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
        'activity_id',
        'user_id',
        'org_id',
        'session_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
        'provider',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_id' => 'string',
            'org_id' => 'string',
            'user_id' => 'string',
            'entity_id' => 'string',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the organization that owns the activity.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

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
    public function scopeByType($query, string $type)
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
    public function scopeByEntity($query, string $entityType, ?string $entityId = null)
    {
        $query = $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        return $query;
    }
}

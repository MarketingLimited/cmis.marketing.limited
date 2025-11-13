<?php

namespace App\Models\Operations;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.audit_logs';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'log_id';

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
        'org_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log_id' => 'string',
            'org_id' => 'string',
            'user_id' => 'string',
            'entity_id' => 'string',
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the organization that owns the audit log.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Log an action.
     *
     * @param string $action
     * @param string $entityType
     * @param string|null $entityId
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param array|null $metadata
     * @return static
     */
    public static function logAction(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): static {
        return static::create([
            'log_id' => \Illuminate\Support\Str::uuid(),
            'org_id' => session('current_org_id'),
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}

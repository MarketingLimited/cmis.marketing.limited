<?php

namespace App\Models\Security;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionContext extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.session_context';
    protected $connection = 'pgsql';
    protected $primaryKey = 'session_id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Timestamps
    const CREATED_AT = 'switched_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'session_id',
        'active_org_id',
        'switched_at',
        'provider',
    ];

    protected $casts = [
        'session_id' => 'string',
        'active_org_id' => 'string',
        'switched_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the active organization
     */
    public function activeOrg()
    {
        return $this->belongsTo(Org::class, 'active_org_id', 'org_id');
    }

    /**
     * Scope to get active sessions
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to get sessions for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('active_org_id', $orgId);
    }

    /**
     * Scope to get recently switched sessions
     */
    public function scopeRecentlySwitched($query, int $minutes = 60)
    {
        return $query->where('switched_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Switch to a different organization
     */
    public function switchOrg(string $newOrgId): bool
    {
        return $this->update([
            'active_org_id' => $newOrgId,
            'switched_at' => now(),
        ]);
    }

    /**
     * Get or create session context
     */
    public static function getOrCreate(string $sessionId, string $orgId, ?string $provider = null): self
    {
        return static::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'active_org_id' => $orgId,
                'provider' => $provider,
                'switched_at' => now(),
            ]
        );
    }

    /**
     * Clean up old sessions
     */
    public static function cleanupOldSessions(int $days = 30): int
    {
        return static::where('switched_at', '<', now()->subDays($days))->delete();
    }
}

<?php

namespace App\Models\Security;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserPermission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.user_permissions';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'org_id',
        'permission_id',
        'is_granted',
        'granted_at',
        'granted_by',
        'expires_at',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'permission_id' => 'string',
        'is_granted' => 'boolean',
        'expires_at' => 'datetime',
        'granted_by' => 'string',
        'granted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');
    }

    /**
     * Get the user who granted this permission
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');
    }

    /**
     * Scope to get granted permissions
     */
    public function scopeGranted($query)
    {
        return $query->where('is_granted', true);
    }

    /**
     * Scope to get revoked permissions
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_granted', false);
    }

    /**
     * Scope to get non-expired permissions
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->where('is_granted', true);
    }

    /**
     * Scope to get expired permissions
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to get permissions for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if permission is currently active
     */
    public function isActive(): bool
    {
        if (!$this->is_granted) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if permission has expired
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

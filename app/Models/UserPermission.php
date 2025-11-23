<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserPermission extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.user_permissions';
    protected $primaryKey = 'id';
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    }
    /**
     * Get the permission
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');

    }
    /**
     * Get the user who granted this permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');

    }
    /**
     * Scope to get active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_granted', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());

    }
    /**
     * Scope to get expired permissions
     */
    public function scopeExpired($query): Builder
    {
        return $query->where('expires_at', '<=', now());

    }
    /**
     * Check if permission is active
     */
    public function isActive(): bool
    {
        if (!$this->is_granted) {
            return false;

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;

        return true;
}
}
}
}
}

<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class PermissionsCache extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.permissions_cache';
    protected $primaryKey = 'permission_id';
    public $timestamps = false;
    protected $fillable = [
        'permission_code',
        'permission_id',
        'category',
        'last_used',
    ];

    protected $casts = [
        'user_id' => 'string',
        'org_id' => 'string',
        'has_permission' => 'boolean',
        'last_used' => 'datetime',
    ];

    /**
     * Scope to get cache for user and org
     */
    public function scopeForUserOrg($query, string $userId, string $orgId): Builder
    {
        return $query->where('user_id', $userId)
            ->where('org_id', $orgId);
    }

    /**
     * Scope to get old cache entries
     */
    public function scopeOld($query, int $days = 30): Builder
    {
        return $query->where('last_used', '<', now()->subDays($days));
    }
}

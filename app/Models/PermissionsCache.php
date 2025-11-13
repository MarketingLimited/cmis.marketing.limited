<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionsCache extends Model
{
    protected $table = 'cmis.permissions_cache';
    protected $primaryKey = 'permission_id';
    protected $connection = 'pgsql';
    public $timestamps = false;
    public $incrementing = false;

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
    public function scopeForUserOrg($query, string $userId, string $orgId)
    {
        return $query->where('user_id', $userId)
            ->where('org_id', $orgId);
    }

    /**
     * Scope to get old cache entries
     */
    public function scopeOld($query, int $days = 30)
    {
        return $query->where('last_used', '<', now()->subDays($days));
    }
}

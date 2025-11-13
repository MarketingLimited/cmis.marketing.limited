<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOrg extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.user_orgs';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'user_id',
        'org_id',
        'role_id',
        'is_active',
        'joined_at',
        'invited_by',
        'last_accessed',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'org_id' => 'string',
        'role_id' => 'string',
        'invited_by' => 'string',
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
        'last_accessed' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that belongs to the organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    /**
     * Get the organization that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the role for this user-org relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /**
     * Get the user who invited this user to the organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by', 'user_id');
    }
}

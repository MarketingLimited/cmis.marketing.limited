<?php

namespace App\Models\Core;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class UserOrg extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.user_orgs';

    protected $primaryKey = 'id';

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
     * Get the role for this user-org relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');

    /**
     * Get the user who invited this user to the organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'invited_by', 'user_id');
}

<?php

namespace App\Models\Security;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityContextAudit extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.security_context_audit';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'org_id',
        'action',
        'success',
        'error_message',
    ];

    protected $casts = [
        'id' => 'string',
        'transaction_id' => 'integer',
        'user_id' => 'string',
        'org_id' => 'string',
        'success' => 'boolean',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope to get audits for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get audits for a specific org
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get successful audits
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to get failed audits
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }
}

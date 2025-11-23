<?php

namespace App\Models\Security;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class SecurityContextAudit extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.security_context_audit';

    protected $primaryKey = 'id';

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
     * Scope to get audits for a specific user
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);

    /**
     * Scope to get audits for a specific org
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);

    /**
     * Scope to get successful audits
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);

    /**
     * Scope to get failed audits
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
}

<?php

namespace App\Models\Compliance;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ComplianceAudit extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.compliance_audits';
    protected $primaryKey = 'audit_id';
    protected $fillable = [
        'audit_id',
        'asset_id',
        'rule_id',
        'status',
        'owner',
        'notes',
        'provider',
    ];

    protected $casts = [
        'audit_id' => 'string',
        'org_id' => 'string',
        'rule_id' => 'string',
        'asset_id' => 'string',
        'content_id' => 'string',
        'reviewed_by' => 'string',
        'created_by' => 'string',
        'violations' => 'array',
        'recommendations' => 'array',
        'auto_fixed' => 'boolean',
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    

    /**
     * Get the compliance rule
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ComplianceRule::class, 'rule_id', 'rule_id');

    }
    /**
     * Get the creative asset
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');

    }
    /**
     * Get the reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by', 'user_id');

    }
    /**
     * Scope passed audits
     */
    public function scopePassed($query): Builder
    {
        return $query->where('audit_result', 'pass');

    }
    /**
     * Scope failed audits
     */
    public function scopeFailed($query): Builder
    {
        return $query->where('audit_result', 'fail');

    }
    /**
     * Scope pending review
     */
    public function scopePendingReview($query): Builder
    {
        return $query->where('status', 'pending')->whereNull('reviewed_at');
}
}

<?php

namespace App\Models\Compliance;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ComplianceRule extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.compliance_rules';
    protected $primaryKey = 'rule_id';
    protected $fillable = [
        'rule_id',
        'code',
        'description',
        'severity',
        'params',
        'provider',
    ];

    protected $casts = ['rule_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'criteria' => 'array',
        'required_fields' => 'array',
        'prohibited_content' => 'array',
        'required_disclaimers' => 'array',
        'approval_required' => 'boolean',
        'auto_fix' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'params' => 'array',
    ];

    

    /**
     * Get compliance audits
     */
    public function audits(): HasMany
    {
        return $this->hasMany(ComplianceAudit::class, 'rule_id', 'rule_id');

    }
    /**
     * Get rule-channel associations
     */
    public function channels()
    {
        return $this->belongsToMany(
            \App\Models\Channel::class,
            'cmis.compliance_rule_channels',
            'rule_id',
            'channel_id'

    }
    /**
     * Scope active rules
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);

    }
    /**
     * Scope by rule type
     */
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('rule_type', $type);

    }
    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity): Builder
    {
        return $query->where('severity', $severity);
}
}

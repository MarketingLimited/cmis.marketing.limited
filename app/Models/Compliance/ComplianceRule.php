<?php

namespace App\Models\Compliance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ComplianceRule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.compliance_rules';
    protected $primaryKey = 'rule_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get compliance audits
     */
    public function audits()
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
        );
    }

    /**
     * Scope active rules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by rule type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
}

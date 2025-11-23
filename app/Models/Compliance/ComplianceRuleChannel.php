<?php

namespace App\Models\Compliance;

use App\Models\BaseModel;

class ComplianceRuleChannel extends BaseModel
{
    protected $table = 'cmis.compliance_rule_channels';
    protected $primaryKey = 'rule_id';
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'channel_id',
        'provider',
    ];

    protected $casts = [
        'rule_id' => 'string',
        'channel_id' => 'integer',
        'is_required' => 'boolean',
    ];

    /**
     * Get the compliance rule
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ComplianceRule::class, 'rule_id', 'rule_id');

    }
    /**
     * Get the channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');
}
}

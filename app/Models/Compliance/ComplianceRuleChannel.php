<?php

namespace App\Models\Compliance;

use Illuminate\Database\Eloquent\Model;

class ComplianceRuleChannel extends Model
{
    protected $table = 'cmis.compliance_rule_channels';
    protected $connection = 'pgsql';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'channel_id',
        'is_required',
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
    public function rule()
    {
        return $this->belongsTo(ComplianceRule::class, 'rule_id', 'rule_id');
    }

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');
    }
}

<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreativeBrief extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.creative_briefs';
    protected $primaryKey = 'brief_id';
    protected $fillable = [
        'brief_id',
        'org_id',
        'name',
        'brief_data',
        'provider',
    ];

    protected $casts = [
        'brief_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'approved_by' => 'string',
        'created_by' => 'string',
        'brief_data' => 'array', // JSONB with validation
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'user_id');
    }

    /**
     * Get creative assets using this brief
     */
    public function creativeAssets()
    {
        return $this->hasMany(\App\Models\CreativeAsset::class, 'brief_id', 'brief_id');
    }

    /**
     * Scope approved briefs
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->whereNotNull('approved_at');
    }

    /**
     * Scope pending briefs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Validate brief structure using DB function
     */
    public function isValid(): bool
    {
        try {
            $result = \DB::selectOne(
                'SELECT cmis.validate_brief_structure(?::jsonb) as is_valid',
                [json_encode($this->brief_data)]
            );

            return (bool) $result->is_valid;
        } catch (\Exception $e) {
            \Log::error('Brief validation failed', [
                'brief_id' => $this->brief_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

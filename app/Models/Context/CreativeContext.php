<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreativeContext extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.creative_contexts';
    protected $primaryKey = 'context_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'brand_voice',
        'visual_guidelines',
        'messaging_framework',
        'content_pillars',
        'keywords',
        'tone',
        'style_guide',
        'do_not_use',
        'target_emotions',
        'call_to_action',
        'metadata',
        'tags',
        'is_active',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'context_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'brand_voice' => 'array',
        'visual_guidelines' => 'array',
        'messaging_framework' => 'array',
        'content_pillars' => 'array',
        'keywords' => 'array',
        'style_guide' => 'array',
        'do_not_use' => 'array',
        'target_emotions' => 'array',
        'call_to_action' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the base context
     */
    public function contextBase()
    {
        return $this->belongsTo(ContextBase::class, 'context_id', 'id');
    }

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active contexts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }
}

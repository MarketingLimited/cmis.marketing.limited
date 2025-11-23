<?php

namespace App\Models\Context;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CreativeContext extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.creative_contexts';
    protected $primaryKey = 'context_id';
    protected $fillable = [
        'context_id',
        'org_id',
        'name',
        'creative_brief',
        'provider',
    ];

    protected $casts = ['context_id' => 'string',
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
        'creative_brief' => 'array',
    ];

    

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

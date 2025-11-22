<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AudioTemplate extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.audio_templates';
    protected $primaryKey = 'atpl_id';
    protected $fillable = [
        'atpl_id',
        'org_id',
        'name',
        'voice_hints',
        'sfx_pack',
        'version',
        'provider',
    ];

    protected $casts = ['template_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'duration' => 'integer',
        'sound_effects' => 'array',
        'script_structure' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'voice_hints' => 'array',
        'sfx_pack' => 'array',
    ];

    

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    /**
     * Scope active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
}

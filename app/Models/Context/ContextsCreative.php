<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContextsCreative extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.contexts_creative';

    protected $primaryKey = 'context_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'context_id',
        'creative_brief',
        'brand_guidelines',
        'visual_style',
    ];

    protected $casts = [
        'context_id' => 'string',
        'brand_guidelines' => 'array',
        'visual_style' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the base context
     */
    public function contextBase()
    {
        return $this->belongsTo(ContextBase::class, 'context_id', 'id');
    }
}

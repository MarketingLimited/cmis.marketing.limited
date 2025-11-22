<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ContextsValue extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.contexts_value';

    protected $primaryKey = 'context_id';

    public $timestamps = false;

    protected $fillable = [
        'context_id',
        'value_proposition',
        'target_audience',
        'key_messages',
    ];

    protected $casts = [
        'context_id' => 'string',
        'target_audience' => 'array',
        'key_messages' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the base context
     */
    public function contextBase()
    {
        return $this->belongsTo(ContextBase::class, 'context_id', 'id');
}

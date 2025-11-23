<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class FlowStep extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.flow_steps';

    protected $primaryKey = 'step_id';

    public $timestamps = false;

    protected $fillable = [
        'step_id',
        'flow_id',
        'ord',
        'type',
        'name',
        'input_map',
        'config',
        'output_map',
        'condition',
        'provider',
    ];

    protected $casts = [
        'step_id' => 'string',
        'flow_id' => 'string',
        'ord' => 'integer',
        'input_map' => 'array',
        'config' => 'array',
        'output_map' => 'array',
        'condition' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the flow
     */
    public function flow()
    {
        return $this->belongsTo(Flow::class, 'flow_id', 'flow_id');

    }
    /**
     * Scope to get steps for a specific flow
     */
    public function scopeForFlow($query, string $flowId)
    {
        return $query->where('flow_id', $flowId);

    }
    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
}
}

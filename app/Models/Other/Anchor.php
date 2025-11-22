<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Anchor extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.anchors';

    protected $primaryKey = 'anchor_id';

    public $timestamps = false;

    protected $fillable = [
        'anchor_id',
        'module_id',
        'code',
        'title',
        'file_ref',
        'section',
        'provider',
    ];

    protected $casts = [
        'anchor_id' => 'string',
        'module_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the module
     */
    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id', 'module_id');
}

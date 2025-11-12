<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anchor extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.anchors';

    protected $primaryKey = 'anchor_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'code',
        'title',
        'file_ref',
        'section',
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
}

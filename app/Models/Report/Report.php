<?php

namespace App\Models\Report;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.reports';

    protected $primaryKey = 'report_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'report_id',
        'org_id',
        'user_id',
        'name',
        'type',
        'status',
        'parameters',
        'format',
        'file_path',
        'generated_at',
    ];

    protected $casts = [
        'report_id' => 'string',
        'org_id' => 'string',
        'user_id' => 'string',
        'parameters' => 'array',
        'generated_at' => 'datetime',
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }
}

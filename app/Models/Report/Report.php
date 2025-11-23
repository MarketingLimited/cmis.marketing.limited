<?php

namespace App\Models\Report;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends BaseModel
{
    use HasOrganization;
protected $table = 'cmis.reports';

    protected $primaryKey = 'report_id';

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

    

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
}
}

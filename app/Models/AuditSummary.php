<?php

namespace App\Models;

use App\Models\BaseModel;

class AuditSummary extends BaseModel
{
    protected $table = 'operations.audit_summary';
    protected $guarded = ['*'];

    public $timestamps = false;
}

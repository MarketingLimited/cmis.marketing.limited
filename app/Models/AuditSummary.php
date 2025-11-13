<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditSummary extends Model
{
    protected $table = 'operations.audit_summary';
    protected $guarded = ['*'];

    public $timestamps = false;
}

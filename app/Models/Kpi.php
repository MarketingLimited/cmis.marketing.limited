<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.kpis';

    protected $primaryKey = 'kpi';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'kpi',
        'description',
    ];

    protected $casts = [
        'kpi' => 'string',
    ];
}

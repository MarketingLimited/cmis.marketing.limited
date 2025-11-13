<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kpis extends Model
{
    protected $table = 'cmis.kpis';
    protected $primaryKey = 'kpi';

    public $timestamps = false;
}

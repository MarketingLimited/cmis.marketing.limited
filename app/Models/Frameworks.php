<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frameworks extends Model
{
    protected $table = 'cmis.frameworks';
    protected $primaryKey = 'framework_id';

    public $timestamps = false;
}

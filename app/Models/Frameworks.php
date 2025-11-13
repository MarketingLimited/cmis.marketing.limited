<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Frameworks extends Model
{
    protected $table = 'cmis.frameworks';

    protected $fillable = [
        'framework_id',
        'framework_name',
        'framework_type',
        'description',
    ];
    protected $primaryKey = 'framework_id';

    public $timestamps = false;
}

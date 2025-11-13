<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industries extends Model
{
    protected $table = 'cmis.industries';

    protected $fillable = [
        'industry_id',
        'name',
    ];
    protected $primaryKey = 'industry_id';

    public $timestamps = false;
}

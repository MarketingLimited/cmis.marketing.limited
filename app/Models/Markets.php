<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Markets extends Model
{
    protected $table = 'cmis.markets';
    protected $primaryKey = 'market_id';

    public $timestamps = false;
}

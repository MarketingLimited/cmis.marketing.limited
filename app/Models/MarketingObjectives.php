<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingObjectives extends Model
{
    protected $table = 'cmis.marketing_objectives';
    protected $guarded = ['*'];

    public $timestamps = false;
}

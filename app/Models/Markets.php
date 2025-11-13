<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Markets extends Model
{
    protected $table = 'cmis.markets';

    protected $fillable = [
        'market_id',
        'market_name',
        'language_code',
        'currency_code',
        'text_direction',
    ];
    protected $primaryKey = 'market_id';

    public $timestamps = false;
}

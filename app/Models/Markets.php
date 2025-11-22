<?php

namespace App\Models;

use App\Models\BaseModel;

class Markets extends BaseModel
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

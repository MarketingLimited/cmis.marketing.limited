<?php

namespace App\Models;

use App\Models\BaseModel;

class VDeletedRecords extends BaseModel
{
    protected $table = 'cmis.v_deleted_records';
    protected $guarded = ['*'];
    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VDeletedRecords extends Model
{
    protected $table = 'cmis.v_deleted_records';

    public $timestamps = false;
}

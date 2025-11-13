<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playbooks extends Model
{
    protected $table = 'cmis.playbooks';
    protected $primaryKey = 'playbook_id';

    public $timestamps = false;
}

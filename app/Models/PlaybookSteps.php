<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybookSteps extends Model
{
    protected $table = 'cmis.playbook_steps';
    protected $primaryKey = 'step_id';

    public $timestamps = false;
}

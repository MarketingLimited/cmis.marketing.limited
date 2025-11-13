<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybookSteps extends Model
{
    protected $table = 'cmis.playbook_steps';

    protected $fillable = [
        'step_id',
        'playbook_id',
        'step_order',
        'step_name',
        'step_instructions',
        'module_reference',
    ];
    protected $primaryKey = 'step_id';

    public $timestamps = false;
}

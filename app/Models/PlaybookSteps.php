<?php

namespace App\Models;

use App\Models\BaseModel;

class PlaybookSteps extends BaseModel
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

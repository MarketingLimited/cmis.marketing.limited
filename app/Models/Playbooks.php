<?php

namespace App\Models;

use App\Models\BaseModel;

class Playbooks extends BaseModel
{
    protected $table = 'cmis.playbooks';

    protected $fillable = [
        'playbook_id',
        'playbook_name',
        'description',
    ];
    protected $primaryKey = 'playbook_id';

    public $timestamps = false;
}

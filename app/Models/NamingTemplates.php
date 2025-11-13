<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NamingTemplates extends Model
{
    protected $table = 'public.naming_templates';

    protected $fillable = [
        'naming_id',
        'scope',
        'template',
    ];
    protected $primaryKey = 'naming_id';

    public $timestamps = false;
}

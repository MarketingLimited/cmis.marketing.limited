<?php

namespace App\Models;

use App\Models\BaseModel;

class VisualDashboardView extends BaseModel
{
    protected $table = 'public.visual_dashboard_view';
    protected $guarded = ['*'];

    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisualDashboardView extends Model
{
    protected $table = 'public.visual_dashboard_view';
    protected $guarded = ['*'];

    public $timestamps = false;
}

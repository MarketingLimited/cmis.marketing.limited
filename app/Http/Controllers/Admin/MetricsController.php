<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MetricsController extends BaseController
{
    public function index(): View
    {
        Gate::authorize('viewInsights', auth()->user());

        $metrics = DB::select('SELECT * FROM cmis_knowledge.semantic_analysis()');
        return view('admin.metrics', compact('metrics'));
    }
}

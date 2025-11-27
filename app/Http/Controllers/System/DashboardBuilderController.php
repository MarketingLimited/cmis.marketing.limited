<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardBuilderController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('system.dashboard-builder.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'critical', // 0/6 models exist!
            'completionPercent' => 30
        ]);
    }
}

<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlertsController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('system.alerts.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // 4/5 models exist, views missing
            'completionPercent' => 45
        ]);
    }
}

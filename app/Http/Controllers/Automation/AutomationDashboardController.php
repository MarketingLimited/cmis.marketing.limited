<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AutomationDashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('automation.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Models exist, views minimal
            'completionPercent' => 55
        ]);
    }
}

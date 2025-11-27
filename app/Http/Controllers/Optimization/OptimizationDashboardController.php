<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OptimizationDashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('optimization.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Models exist, views missing
            'completionPercent' => 50
        ]);
    }
}

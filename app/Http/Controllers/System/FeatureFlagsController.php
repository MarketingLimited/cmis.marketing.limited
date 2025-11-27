<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FeatureFlagsController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('system.feature-flags.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'critical', // 0/3 models exist, no routes!
            'completionPercent' => 20
        ]);
    }
}

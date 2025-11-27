<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrchestrationController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('orchestration.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Models exist, views missing
            'completionPercent' => 40
        ]);
    }
}

<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportsController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('system.exports.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Controllers exist, views minimal
            'completionPercent' => 50
        ]);
    }
}

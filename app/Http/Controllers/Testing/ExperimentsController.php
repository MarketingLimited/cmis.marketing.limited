<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExperimentsController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('testing.experiments.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Models exist, views missing
            'completionPercent' => 40
        ]);
    }
}

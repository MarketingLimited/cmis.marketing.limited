<?php

namespace App\Http\Controllers\Listening;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialListeningController extends Controller
{
    public function index(Request $request)
    {
        $orgId = $request->route('org');

        return view('listening.index', [
            'currentOrg' => $orgId,
            'moduleStatus' => 'partial', // Models exist, views incomplete
            'completionPercent' => 35
        ]);
    }
}

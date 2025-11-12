<?php

namespace App\Http\Controllers;

use App\Models\CreativeAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CreativeController extends Controller
{
    public function show($id)
    {
        // Use Eloquent model for proper authorization
        $creative = CreativeAsset::where('org_id', session('current_org_id'))
            ->where('asset_id', $id)
            ->firstOrFail();

        $this->authorize('view', $creative);

        return view('creatives.show', [
            'creative' => $creative
        ]);
    }
}
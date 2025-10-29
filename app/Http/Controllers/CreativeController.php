<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CreativeController extends Controller
{
    public function show($id)
    {
        $creative = DB::table('cmis.creative_assets')->where('asset_id', $id)->first();

        if (!$creative) {
            abort(404, 'الأصل الإبداعي غير موجود');
        }

        return view('creatives.show', [
            'creative' => $creative
        ]);
    }
}
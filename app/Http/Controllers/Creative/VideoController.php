<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;

/**
 * Class VideoController
 * مسؤول عن مكتبة الفيديوهات والقوالب المرئية داخل النظام.
 */
class VideoController extends Controller
{
    /**
     * عرض مكتبة الفيديوهات.
     */
    public function index()
    {
        $this->authorize('viewAny', CreativeAsset::class);

        return view('creative.videos.index');
    }
}
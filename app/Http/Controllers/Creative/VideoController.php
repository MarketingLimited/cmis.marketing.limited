<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class VideoController
 * مسؤول عن مكتبة الفيديوهات والقوالب المرئية داخل النظام.
 */
class VideoController extends Controller
{
    /**
     * عرض مكتبة الفيديوهات.
     */
    public function index(): View
    {
        $this->authorize('viewAny', CreativeAsset::class);

        return view('creative.videos.index');
    }
}
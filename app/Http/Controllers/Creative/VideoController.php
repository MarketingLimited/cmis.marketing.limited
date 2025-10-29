<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
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
        return view('creative.videos.index');
    }
}
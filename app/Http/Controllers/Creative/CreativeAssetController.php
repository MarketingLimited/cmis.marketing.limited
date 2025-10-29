<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class CreativeAssetController
 * مسؤول عن إدارة الأصول الإبداعية مثل الصور والفيديوهات والرسومات.
 */
class CreativeAssetController extends Controller
{
    /**
     * عرض قائمة الأصول الإبداعية.
     */
    public function index()
    {
        return view('creative.assets.index');
    }
}
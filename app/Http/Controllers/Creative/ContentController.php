<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class ContentController
 * يدير إدارة المحتوى النصي والمخططات التحريرية الخاصة بالحملات.
 */
class ContentController extends Controller
{
    /**
     * عرض قائمة المحتويات النصية.
     */
    public function index()
    {
        return view('creative.content.index');
    }
}
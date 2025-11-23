<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\Creative\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class ContentController
 * يدير إدارة المحتوى النصي والمخططات التحريرية الخاصة بالحملات.
 */
class ContentController extends Controller
{
    /**
     * عرض قائمة المحتويات النصية.
     */
    public function index(): View
    {
        $this->authorize('viewAny', ContentItem::class);

        return view('creative.content.index');
    }
}
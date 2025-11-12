<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\CreativeAsset;
use Illuminate\Http\Request;

/**
 * Class CopyController
 * مسؤول عن النصوص الإعلانية: العناوين، الأوصاف، الشعارات الإبداعية.
 */
class CopyController extends Controller
{
    /**
     * عرض قائمة النصوص الإعلانية.
     */
    public function index()
    {
        $this->authorize('viewAny', CreativeAsset::class);

        return view('creative.copy.index');
    }
}
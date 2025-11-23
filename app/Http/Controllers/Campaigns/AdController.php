<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class AdController
 * يتحكم في الحملات الإعلانية الفرعية (مثل حملات Facebook, Google, وغيرها)
 */
class AdController extends Controller
{
    /**
     * عرض قائمة الحملات الإعلانية.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('campaigns.ads.index');
    }
}
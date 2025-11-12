<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

/**
 * Class AdController
 * يتحكم في الحملات الإعلانية الفرعية (مثل حملات Facebook, Google, وغيرها)
 */
class AdController extends Controller
{
    /**
     * عرض قائمة الحملات الإعلانية.
     */
    public function index()
    {
        $this->authorize('viewAny', Campaign::class);

        return view('campaigns.ads.index');
    }
}
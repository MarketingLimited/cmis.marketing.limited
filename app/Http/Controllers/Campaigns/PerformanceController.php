<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class PerformanceController
 * يعرض مؤشرات الأداء الخاصة بالحملات التسويقية.
 */
class PerformanceController extends Controller
{
    /**
     * عرض تقارير الأداء لجميع الحملات.
     */
    public function index(): View
    {
        $this->authorize('viewAnalytics', Campaign::class);

        return view('campaigns.performance.index');
    }
}
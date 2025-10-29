<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class PerformanceController
 * يعرض مؤشرات الأداء الخاصة بالحملات التسويقية.
 */
class PerformanceController extends Controller
{
    /**
     * عرض تقارير الأداء لجميع الحملات.
     */
    public function index()
    {
        return view('campaigns.performance.index');
    }
}
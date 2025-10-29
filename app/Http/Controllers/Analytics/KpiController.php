<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class KpiController
 * مسؤول عن إدارة مؤشرات الأداء الرئيسية (KPIs) وتحليلها.
 */
class KpiController extends Controller
{
    /**
     * عرض قائمة مؤشرات الأداء الرئيسية.
     */
    public function index()
    {
        return view('analytics.kpis.index');
    }
}
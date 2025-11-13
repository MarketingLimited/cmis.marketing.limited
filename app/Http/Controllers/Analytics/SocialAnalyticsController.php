<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class SocialAnalyticsController
 * مسؤول عن تحليل أداء الحسابات والمنشورات في وسائل التواصل الاجتماعي.
 */
class SocialAnalyticsController extends Controller
{
    /**
     * عرض تقارير الأداء الخاصة بالسوشيال ميديا.
     */
    public function index()
    {
        Gate::authorize('viewPerformance', auth()->user());

        return view('analytics.social.index');
    }
}
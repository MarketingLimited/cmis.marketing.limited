<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Class SocialAnalyticsController
 * مسؤول عن تحليل أداء الحسابات والمنشورات في وسائل التواصل الاجتماعي.
 */
class SocialAnalyticsController extends Controller
{
    /**
     * عرض تقارير الأداء الخاصة بالسوشيال ميديا.
     */
    public function index(): View
    {
        Gate::authorize('viewPerformance', auth()->user());

        return view('analytics.social.index');
    }
}
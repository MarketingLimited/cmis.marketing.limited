<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        return view('analytics.social.index');
    }
}
<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class AIInsightsController
 * يعرض التحليلات التنبؤية والرؤى المستخلصة من بيانات الذكاء الاصطناعي.
 */
class AIInsightsController extends Controller
{
    /**
     * عرض الرؤى والتحليلات التنبؤية.
     */
    public function index()
    {
        Gate::authorize('viewInsights', auth()->user());

        return view('ai.insights.index');
    }
}
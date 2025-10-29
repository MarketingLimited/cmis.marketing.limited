<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        return view('ai.insights.index');
    }
}
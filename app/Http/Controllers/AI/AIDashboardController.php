<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class AIDashboardController
 * لوحة تحكم الذكاء الاصطناعي - تعرض نظرة عامة على الحملات والتحليلات الذكية.
 */
class AIDashboardController extends Controller
{
    /**
     * عرض لوحة تحكم الذكاء الاصطناعي.
     */
    public function index()
    {
        return view('ai.dashboard.index');
    }
}
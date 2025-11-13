<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Class ExportController
 * مسؤول عن تصدير التقارير التحليلية والبيانات إلى صيغ مختلفة (PDF, Excel, CSV).
 */
class ExportController extends Controller
{
    /**
     * عرض واجهة تصدير التقارير.
     */
    public function index()
    {
        Gate::authorize('exportData', auth()->user());

        return view('analytics.export.index');
    }
}
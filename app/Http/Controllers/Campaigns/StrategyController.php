<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class StrategyController
 * مسؤول عن إدارة استراتيجيات الحملات التسويقية (الأهداف، القنوات، الجمهور)
 */
class StrategyController extends Controller
{
    /**
     * عرض قائمة الاستراتيجيات التسويقية.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('campaigns.strategies.index');
    }
}
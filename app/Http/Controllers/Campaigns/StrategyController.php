<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class StrategyController
 * مسؤول عن إدارة استراتيجيات الحملات التسويقية (الأهداف، القنوات، الجمهور)
 */
class StrategyController extends Controller
{
    /**
     * عرض قائمة الاستراتيجيات التسويقية.
     */
    public function index()
    {
        return view('campaigns.strategies.index');
    }
}
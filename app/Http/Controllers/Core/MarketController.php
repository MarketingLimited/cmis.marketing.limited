<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class MarketController
 * مسؤول عن إدارة الأسواق والقطاعات داخل النظام.
 */
class MarketController extends Controller
{
    /**
     * عرض قائمة الأسواق أو القطاعات.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Org::class);

        return view('core.markets.index');
    }
}
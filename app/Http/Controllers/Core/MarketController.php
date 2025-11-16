<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use Illuminate\Http\Request;

/**
 * Class MarketController
 * مسؤول عن إدارة الأسواق والقطاعات داخل النظام.
 */
class MarketController extends Controller
{
    /**
     * عرض قائمة الأسواق أو القطاعات.
     */
    public function index()
    {
        $this->authorize('viewAny', Org::class);

        return view('core.markets.index');
    }
}
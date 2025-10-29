<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
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
        return view('core.markets.index');
    }
}
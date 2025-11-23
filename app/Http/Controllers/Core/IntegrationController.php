<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class IntegrationController
 * يتحكم في إدارة تكاملات الأنظمة (مثل Meta, Google, FTP, APIs)
 */
class IntegrationController extends Controller
{
    /**
     * عرض قائمة التكاملات النشطة
     */
    public function index(): View
    {
        return view('core.integrations.index');
    }
}
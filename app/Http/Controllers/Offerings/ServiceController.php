<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class ServiceController
 * مسؤول عن إدارة الخدمات داخل النظام (إضافة، تعديل، حذف، عرض التفاصيل)
 */
class ServiceController extends Controller
{
    /**
     * عرض قائمة الخدمات.
     */
    public function index()
    {
        return view('offerings.services.index');
    }
}
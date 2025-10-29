<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class ProductController
 * مسؤول عن إدارة المنتجات في النظام (إضافة، تعديل، حذف، عرض التفاصيل)
 */
class ProductController extends Controller
{
    /**
     * عرض قائمة المنتجات.
     */
    public function index()
    {
        return view('offerings.products.index');
    }
}
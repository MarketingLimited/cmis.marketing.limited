<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class BundleController
 * يدير العروض المجمعة (Bundles) التي تحتوي على منتجات وخدمات معًا.
 */
class BundleController extends Controller
{
    /**
     * عرض قائمة العروض المجمعة.
     */
    public function index()
    {
        return view('offerings.bundles.index');
    }
}
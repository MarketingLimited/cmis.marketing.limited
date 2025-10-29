<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use App\Models\Offering;

class BundleController extends Controller
{
    public function index()
    {
        $offerings = Offering::query()
            ->with('org:org_id,name')
            ->where('kind', 'bundle')
            ->orderBy('name')
            ->get();

        return view('offerings.list', [
            'title' => '🎁 الباقات',
            'description' => 'الباقات التي تجمع بين المنتجات والخدمات لتسهيل عرض القيمة.',
            'offerings' => $offerings,
        ]);
    }
}
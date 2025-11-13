<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use App\Models\Offering;

class ServiceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Offering::class);

        $offerings = Offering::query()
            ->with('org:org_id,name')
            ->where('kind', 'service')
            ->orderBy('name')
            ->get();

        return view('offerings.list', [
            'title' => '🧰 الخدمات',
            'description' => 'الخدمات داخل النظام مع تفاصيل المؤسسات المالكة.',
            'offerings' => $offerings,
        ]);
    }
}
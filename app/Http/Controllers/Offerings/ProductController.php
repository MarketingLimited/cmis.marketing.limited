<?php

namespace App\Http\Controllers\Offerings;

use App\Http\Controllers\Controller;
use App\Models\Offering;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Offering::class);

        $offerings = Offering::query()
            ->with('org:org_id,name')
            ->where('kind', 'product')
            ->orderBy('name')
            ->get();

        return view('offerings.list', [
            'title' => '📦 المنتجات',
            'description' => 'جميع المنتجات المسجلة ضمن المؤسسات المختلفة داخل منصة CMIS.',
            'offerings' => $offerings,
        ]);
    }
}
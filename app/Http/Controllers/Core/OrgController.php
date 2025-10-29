<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class OrgController
 * يتحكم في إدارة المؤسسات داخل النظام (إنشاء، عرض، تعديل، حذف)
 */
class OrgController extends Controller
{
    /**
     * عرض قائمة المؤسسات
     */
    public function index()
    {
        return view('core.orgs.index');
    }
}
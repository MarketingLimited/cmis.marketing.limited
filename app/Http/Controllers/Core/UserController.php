<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class UserController
 * مسؤول عن إدارة المستخدمين داخل النظام (عرض، صلاحيات، إنشاء، تعديل، حذف)
 */
class UserController extends Controller
{
    /**
     * عرض قائمة المستخدمين
     */
    public function index()
    {
        return view('core.users.index');
    }
}
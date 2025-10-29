<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class SocialAccountController
 * مسؤول عن إدارة الحسابات الاجتماعية (Facebook, Instagram, TikTok, وغيرها).
 */
class SocialAccountController extends Controller
{
    /**
     * عرض قائمة الحسابات الاجتماعية.
     */
    public function index()
    {
        return view('channels.social.accounts.index');
    }
}
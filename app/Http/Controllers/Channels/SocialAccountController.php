<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\Channel;
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
        $this->authorize('viewAny', Channel::class);

        return view('channels.social.accounts.index');
    }
}
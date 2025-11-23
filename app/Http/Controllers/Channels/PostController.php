<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class PostController
 * مسؤول عن إدارة المنشورات والمحتوى المنشور على القنوات الاجتماعية.
 */
class PostController extends Controller
{
    /**
     * عرض قائمة المنشورات.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Channel::class);

        return view('channels.social.posts.index');
    }
}
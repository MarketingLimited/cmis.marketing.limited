<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Class ChannelController
 * مسؤول عن إدارة القنوات والمنصات التسويقية المختلفة داخل النظام.
 */
class ChannelController extends Controller
{
    /**
     * عرض قائمة القنوات والمنصات.
     */
    public function index()
    {
        return view('channels.index');
    }
}
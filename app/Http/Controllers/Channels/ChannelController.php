<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

class ChannelController extends Controller
{
    public function index()
    {
        $channels = Cache::remember('channels.index', now()->addMinutes(5), function () {
            return Channel::query()
                ->with('formats:format_id,channel_id,code,ratio,length_hint')
                ->orderBy('name')
                ->get(['channel_id', 'name', 'code', 'constraints']);
        });

        $searchable = $channels->map(fn ($channel) => [
            'name' => $channel->name,
            'code' => $channel->code,
            'status' => $channel->constraints['status'] ?? 'غير محدد',
        ]);

        return view('channels.index', [
            'channels' => $channels,
            'searchableChannels' => $searchable,
        ]);
    }
}
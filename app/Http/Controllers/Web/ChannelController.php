<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * ChannelController for Web Routes
 *
 * Updated to use URL-based org parameter
 */
class ChannelController extends Controller
{
    /**
     * Display a listing of channels for the organization
     */
    public function index(string $org)
    {
        $this->authorize('viewAny', Channel::class);

        $stats = Cache::remember("channels.stats.{$org}", now()->addMinutes(5), function () use ($org) {
            return [
                'total' => Channel::where('org_id', $org)->count(),
                'active' => Channel::where('org_id', $org)
                    ->where('status', 'active')
                    ->count(),
                'inactive' => Channel::where('org_id', $org)
                    ->where('status', 'inactive')
                    ->count(),
            ];
        });

        $channels = Channel::query()
            ->where('org_id', $org)
            ->with(['org:org_id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('channels.index', [
            'stats' => $stats,
            'channels' => $channels,
        ]);
    }

    /**
     * Display the specified channel
     */
    public function show(string $org, $channelId)
    {
        $channel = Channel::query()
            ->where('org_id', $org)
            ->with(['org:org_id,name'])
            ->findOrFail($channelId);

        $this->authorize('view', $channel);

        return view('channels.show', [
            'channel' => $channel,
        ]);
    }
}

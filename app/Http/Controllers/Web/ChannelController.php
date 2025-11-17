<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Support\Facades\Cache;

/**
 * ChannelController for Web Routes
 *
 * هذا Controller مخصص للـ web routes ولا يحتاج orgId parameter
 * يستخدم session('current_org_id') للحصول على المنظمة الحالية
 */
class ChannelController extends Controller
{
    /**
     * Display a listing of channels for the current organization
     */
    public function index()
    {
        $this->authorize('viewAny', Channel::class);

        $orgId = session('current_org_id');

        if (!$orgId) {
            return redirect()->route('dashboard')
                ->with('error', 'الرجاء اختيار منظمة أولاً');
        }

        $stats = Cache::remember("channels.stats.{$orgId}", now()->addMinutes(5), function () use ($orgId) {
            return [
                'total' => Channel::where('org_id', $orgId)->count(),
                'active' => Channel::where('org_id', $orgId)
                    ->where('status', 'active')
                    ->count(),
                'inactive' => Channel::where('org_id', $orgId)
                    ->where('status', 'inactive')
                    ->count(),
            ];
        });

        $channels = Channel::query()
            ->where('org_id', $orgId)
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
    public function show($channelId)
    {
        $orgId = session('current_org_id');

        $channel = Channel::query()
            ->where('org_id', $orgId)
            ->with(['org:org_id,name'])
            ->findOrFail($channelId);

        $this->authorize('view', $channel);

        return view('channels.show', [
            'channel' => $channel,
        ]);
    }
}

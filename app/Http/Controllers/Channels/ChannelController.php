<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index(Request $request, string $orgId)
    {
        try {
            $channels = Channel::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($channels);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch channels'], 500);
        }
    }

    public function store(Request $request, string $orgId)
    {
        try {
            $channel = Channel::create(array_merge(
                $request->all(),
                ['org_id' => $orgId]
            ));

            return response()->json(['message' => 'Channel created', 'channel' => $channel], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create channel'], 500);
        }
    }

    public function show(Request $request, string $orgId, string $channelId)
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            return response()->json(['channel' => $channel]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Channel not found'], 404);
        }
    }

    public function update(Request $request, string $orgId, string $channelId)
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            $channel->update($request->all());
            return response()->json(['message' => 'Channel updated', 'channel' => $channel]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update'], 500);
        }
    }

    public function destroy(Request $request, string $orgId, string $channelId)
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            $channel->delete();
            return response()->json(['message' => 'Channel deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete'], 500);
        }
    }
}

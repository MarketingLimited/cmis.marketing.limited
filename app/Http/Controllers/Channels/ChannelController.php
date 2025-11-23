<?php

namespace App\Http\Controllers\Channels;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Channel::class);

        try {
            $channels = Channel::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return $this->success($channels, 'Retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch channels');
        }
    }

    public function store(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('create', Channel::class);

        try {
            $channel = Channel::create(array_merge(
                $request->all(),
                ['org_id' => $orgId]
            ));

            return response()->json(['message' => 'Channel created', 'channel' => $channel], 201);

        } catch (\Exception $e) {
            return $this->serverError('Failed to create channel');
        }
    }

    public function show(Request $request, string $orgId, string $channelId): JsonResponse
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            $this->authorize('view', $channel);
            return response()->json(['channel' => $channel]);
        } catch (\Exception $e) {
            return $this->notFound('Channel not found');
        }
    }

    public function update(Request $request, string $orgId, string $channelId): JsonResponse
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            $this->authorize('update', $channel);
            $channel->update($request->all());
            return response()->json(['message' => 'Channel updated', 'channel' => $channel]);
        } catch (\Exception $e) {
            return $this->serverError('Failed to update');
        }
    }

    public function destroy(Request $request, string $orgId, string $channelId): JsonResponse
    {
        try {
            $channel = Channel::where('org_id', $orgId)->findOrFail($channelId);
            $this->authorize('delete', $channel);
            $channel->delete();
            return response()->json(['message' => 'Channel deleted']);
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete');
        }
    }
}

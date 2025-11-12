<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    public function index(Request $request, string $orgId)
    {
        $this->authorize('viewAny', Campaign::class);

        try {
            $perPage = $request->input('per_page', 20);
            $status = $request->input('status');
            $search = $request->input('search');

            $query = Campaign::where('org_id', $orgId);

            if ($status) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where('name', 'ilike', "%{$search}%");
            }

            $campaigns = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json($campaigns);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch campaigns',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, string $orgId)
    {
        $this->authorize('create', Campaign::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'objective' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $campaign = Campaign::create([
                'org_id' => $orgId,
                'name' => $request->name,
                'objective' => $request->objective,
                'status' => 'draft',
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'budget' => $request->budget,
                'currency' => $request->currency ?? 'BHD',
                'created_by' => $request->user()->user_id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Campaign created', 'campaign' => $campaign->fresh()], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create campaign', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, string $orgId, string $campaignId)
    {
        try {
            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            $this->authorize('view', $campaign);
            return response()->json(['campaign' => $campaign]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }
    }

    public function update(Request $request, string $orgId, string $campaignId)
    {
        $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
        $this->authorize('update', $campaign);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'objective' => 'sometimes|string',
            'status' => 'sometimes|in:draft,active,paused,completed,archived',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'budget' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        try {
            $campaign->update($request->only(['name', 'objective', 'status', 'start_date', 'end_date', 'budget', 'currency']));
            return response()->json(['message' => 'Campaign updated', 'campaign' => $campaign->fresh()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Campaign not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, string $orgId, string $campaignId)
    {
        try {
            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            $this->authorize('delete', $campaign);
            $campaign->delete();
            return response()->json(['message' => 'Campaign deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete'], 500);
        }
    }
}

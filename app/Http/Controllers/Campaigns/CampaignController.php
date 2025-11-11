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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'objective' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'total_budget' => 'nullable|numeric|min:0',
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
                'total_budget' => $request->total_budget,
                'spent_budget' => 0,
                'currency' => $request->currency ?? 'BHD',
            ]);

            DB::commit();

            return response()->json(['message' => 'Campaign created', 'campaign' => $campaign], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create campaign', 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Request $request, string $orgId, string $campaignId)
    {
        try {
            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            return response()->json(['campaign' => $campaign]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }
    }

    public function update(Request $request, string $orgId, string $campaignId)
    {
        try {
            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            $campaign->update($request->only(['name', 'objective', 'status', 'start_date', 'end_date', 'total_budget']));
            return response()->json(['message' => 'Campaign updated', 'campaign' => $campaign]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update'], 500);
        }
    }

    public function destroy(Request $request, string $orgId, string $campaignId)
    {
        try {
            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            $campaign->delete();
            return response()->json(['message' => 'Campaign deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete'], 500);
        }
    }
}

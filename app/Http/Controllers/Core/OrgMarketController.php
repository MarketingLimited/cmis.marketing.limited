<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Market\OrgMarket;
use App\Models\Market\Market;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Controllers\Concerns\ApiResponse;

class OrgMarketController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of organization markets.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrgMarket::query()
            ->where('org_id', $request->user()->current_org_id)
            ->with(['market', 'org']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('min_priority')) {
            $query->where('priority_level', '>=', $request->min_priority);
        }

        // Filter by primary market
        if ($request->has('is_primary')) {
            $query->where('is_primary_market', filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'priority_level');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $markets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $markets->items(),
            'meta' => [
                'current_page' => $markets->currentPage(),
                'last_page' => $markets->lastPage(),
                'per_page' => $markets->perPage(),
                'total' => $markets->total(),
            ]
        ]);
    }

    /**
     * Store a newly created organization market.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'market_id' => 'required|uuid|exists:cmis.markets,market_id',
            'entry_date' => 'nullable|date',
            'status' => 'required|in:planning,entering,active,exiting,exited',
            'market_share' => 'nullable|numeric|min:0|max:100',
            'priority_level' => 'required|integer|min:1|max:10',
            'investment_budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|array',
            'positioning_strategy' => 'nullable|array',
            'competitive_advantages' => 'nullable|array',
            'challenges' => 'nullable|array',
            'opportunities' => 'nullable|array',
            'is_primary_market' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        // Check if market already exists for this org
        $exists = OrgMarket::where('org_id', $request->user()->current_org_id)
            ->where('market_id', $request->market_id)
            ->exists();

        if ($exists) {
            return $this->error('This market is already added to your organization', 422);
        }

        $data = $validator->validated();
        $data['org_id'] = $request->user()->current_org_id;

        $orgMarket = OrgMarket::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Market added successfully',
            'data' => $orgMarket->load(['market', 'org'])
        ], 201);
    }

    /**
     * Display the specified organization market.
     */
    public function show(Request $request, string $market_id): JsonResponse
    {
        $orgMarket = OrgMarket::with(['market', 'org'])
            ->where('market_id', $market_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        return $this->success($orgMarket
        );
    }

    /**
     * Update the specified organization market.
     */
    public function update(Request $request, string $market_id): JsonResponse
    {
        $orgMarket = OrgMarket::where('market_id', $market_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'entry_date' => 'nullable|date',
            'status' => 'sometimes|in:planning,entering,active,exiting,exited',
            'market_share' => 'nullable|numeric|min:0|max:100',
            'priority_level' => 'sometimes|integer|min:1|max:10',
            'investment_budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|array',
            'positioning_strategy' => 'nullable|array',
            'competitive_advantages' => 'nullable|array',
            'challenges' => 'nullable|array',
            'opportunities' => 'nullable|array',
            'is_primary_market' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $orgMarket->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Market updated successfully',
            'data' => $orgMarket->fresh()->load(['market', 'org'])
        ]);
    }

    /**
     * Remove the specified organization market.
     */
    public function destroy(Request $request, string $market_id): JsonResponse
    {
        $orgMarket = OrgMarket::where('market_id', $market_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $orgMarket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Market removed successfully'
        ]);
    }

    /**
     * Get available markets (not yet added to organization)
     */
    public function available(Request $request): JsonResponse
    {
        $orgMarkets = OrgMarket::where('org_id', $request->user()->current_org_id)
            ->pluck('market_id');

        $markets = Market::whereNotIn('market_id', $orgMarkets)
            ->orderBy('name')
            ->get();

        return $this->success($markets
        );
    }

    /**
     * Get market statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $orgId = $request->user()->current_org_id;

        $stats = [
            'total_markets' => OrgMarket::where('org_id', $orgId)->count(),
            'active_markets' => OrgMarket::where('org_id', $orgId)->where('status', 'active')->count(),
            'primary_markets' => OrgMarket::where('org_id', $orgId)->where('is_primary_market', true)->count(),
            'total_investment' => OrgMarket::where('org_id', $orgId)->sum('investment_budget'),
            'by_status' => OrgMarket::where('org_id', $orgId)
                ->select('status', \DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
            'avg_priority' => OrgMarket::where('org_id', $orgId)->avg('priority_level'),
        ];

        return $this->success($stats
        );
    }

    /**
     * Calculate ROI for a market
     */
    public function calculateRoi(Request $request, string $market_id): JsonResponse
    {
        $orgMarket = OrgMarket::where('market_id', $market_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'revenue' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $roi = $orgMarket->calculateRoi($request->revenue);

        return response()->json([
            'success' => true,
            'data' => [
                'market_id' => $market_id,
                'investment' => $orgMarket->investment_budget,
                'revenue' => $request->revenue,
                'roi_percentage' => $roi,
                'profit' => $request->revenue - $orgMarket->investment_budget,
            ]
        ]);
    }
}

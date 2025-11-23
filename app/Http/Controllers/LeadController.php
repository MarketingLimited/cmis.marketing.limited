<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Models\Lead\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for managing leads
 * Handles lead CRUD operations, scoring, and status management
 */
class LeadController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List leads
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $query = Lead::where('org_id', $orgId);

            // Filter by campaign
            if ($request->has('campaign_id')) {
                $query->where('campaign_id', $request->input('campaign_id'));
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Filter by source
            if ($request->has('source')) {
                $query->where('source', $request->input('source'));
            }

            // Filter by minimum score
            if ($request->has('min_score')) {
                $query->where('score', '>=', $request->input('min_score'));
            }

            // Search by name or email
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->input('per_page', 20);
            $leads = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->success($leads->items(),
                'total' => $leads->total(),
                'per_page' => $leads->perPage(),
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
            , 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error("Failed to list leads: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new lead
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'campaign_id' => 'nullable|string',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:50',
                'source' => 'nullable|string|max:100',
                'status' => 'nullable|string|in:new,contacted,qualified,converted,lost',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $lead = Lead::create([
                'org_id' => $orgId,
                'campaign_id' => $request->input('campaign_id'),
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'source' => $request->input('source', 'manual'),
                'status' => $request->input('status', 'new'),
                'score' => $this->calculateInitialScore($request),
                'metadata' => $request->input('metadata', []),
            ]);

            return $this->created($lead, 'Lead created successfully');
        } catch (\Exception $e) {
            Log::error("Failed to create lead: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show lead details
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $lead = Lead::where('lead_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            return $this->success($lead, 'Retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get lead: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update lead
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $lead = Lead::where('lead_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'phone' => 'sometimes|string|max:50',
                'status' => 'sometimes|string|in:new,contacted,qualified,converted,lost',
                'metadata' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $lead->update($request->only([
                'name',
                'email',
                'phone',
                'status',
                'metadata',
            ]));

            return $this->success($lead, 'Lead updated successfully');
        } catch (\Exception $e) {
            Log::error("Failed to update lead: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete lead
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $lead = Lead::where('lead_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Soft delete
            $lead->delete();

            return $this->success(['message' => 'Lead deleted successfully'
            ], 'Operation completed successfully');
        } catch (\Exception $e) {
            Log::error("Failed to delete lead: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lead score
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function score(string $id, Request $request): JsonResponse
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            $lead = Lead::where('lead_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            // Recalculate score based on various factors
            $score = $this->calculateLeadScore($lead);

            $lead->update(['score' => $score]);

            return response()->json([
                'data' => [
                    'lead_id' => $lead->lead_id,
                    'score' => $score,
                    'score_breakdown' => $this->getScoreBreakdown($lead),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get lead score: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update lead status
     *
     * @param string $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(string $id, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:new,contacted,qualified,converted,lost',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }

            $orgId = $this->resolveOrgId($request);
            $user = $request->user();

            if (!$orgId) {
                return $this->notFound('No active organization found');
            }

            // Initialize RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $orgId
            ]);

            $lead = Lead::where('lead_id', $id)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $lead->update(['status' => $request->input('status')]);

            return $this->success($lead, 'Lead status updated successfully');
        } catch (\Exception $e) {
            Log::error("Failed to update lead status: {$e->getMessage()}");
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate initial score for new lead
     *
     * @param Request $request
     * @return int
     */
    private function calculateInitialScore(Request $request): int
    {
        $score = 50; // Base score

        // Has email
        if ($request->has('email') && $request->input('email')) {
            $score += 20;
        }

        // Has phone
        if ($request->has('phone') && $request->input('phone')) {
            $score += 15;
        }

        // Has metadata
        if ($request->has('metadata') && is_array($request->input('metadata')) && count($request->input('metadata')) > 0) {
            $score += 15;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Calculate lead score based on various factors
     *
     * @param Lead $lead
     * @return int
     */
    private function calculateLeadScore(Lead $lead): int
    {
        $score = 50; // Base score

        // Has email
        if ($lead->email) {
            $score += 20;
        }

        // Has phone
        if ($lead->phone) {
            $score += 15;
        }

        // Status bonus
        $statusScores = [
            'new' => 0,
            'contacted' => 10,
            'qualified' => 20,
            'converted' => 30,
            'lost' => -20,
        ];
        $score += $statusScores[$lead->status] ?? 0;

        // Has metadata
        if (is_array($lead->metadata) && count($lead->metadata) > 0) {
            $score += 15;
        }

        return max(0, min($score, 100)); // Cap between 0-100
    }

    /**
     * Get score breakdown
     *
     * @param Lead $lead
     * @return array
     */
    private function getScoreBreakdown(Lead $lead): array
    {
        return [
            'base_score' => 50,
            'email_bonus' => $lead->email ? 20 : 0,
            'phone_bonus' => $lead->phone ? 15 : 0,
            'status_bonus' => [
                'new' => 0,
                'contacted' => 10,
                'qualified' => 20,
                'converted' => 30,
                'lost' => -20,
            ][$lead->status] ?? 0,
            'metadata_bonus' => (is_array($lead->metadata) && count($lead->metadata) > 0) ? 15 : 0,
        ];
    }

    /**
     * Resolve organization ID from request
     *
     * @param Request $request
     * @return string|null
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        // Try to get from route parameter first
        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        // Fall back to user's active org
        if ($user->active_org_id) {
            return $user->active_org_id;
        }

        // Query the user_orgs pivot table for an active org
        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}

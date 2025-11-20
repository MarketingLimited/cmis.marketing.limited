<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Content\ContentPlanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Display a listing of content items.
     */
    public function index(Request $request)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            // Validate query parameters
            $validated = $request->validate([
                'status' => ['sometimes', 'string', 'in:draft,scheduled,published,archived'],
                'search' => ['sometimes', 'string', 'max:255'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $query = ContentPlanItem::where('org_id', $orgId);

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['search'])) {
                $query->where('title', 'ilike', "%{$validated['search']}%");
            }

            // Pagination
            $content = $query->orderBy('created_at', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'data' => $content->items(),
                'meta' => [
                    'current_page' => $content->currentPage(),
                    'per_page' => $content->perPage(),
                    'total' => $content->total(),
                    'last_page' => $content->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Content index error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created content item.
     */
    public function store(Request $request)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            // Initialize RLS context for multi-tenancy
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $request->user()->user_id,
                $orgId
            ]);

            // Validate request
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'body' => ['nullable', 'string'],
                'content_type' => ['nullable', 'string'],
                'platform' => ['nullable', 'string'],
                'status' => ['sometimes', 'string', 'in:draft,scheduled,published,archived'],
                'scheduled_at' => ['nullable', 'date'],
                'plan_id' => ['nullable', 'uuid'],
            ]);

            $validated['org_id'] = $orgId;
            $validated['item_id'] = Str::uuid()->toString();
            $validated['created_by'] = $request->user()->user_id;

            if (!isset($validated['status'])) {
                $validated['status'] = 'draft';
            }

            // If no plan_id provided, create a default plan for this content
            if (!isset($validated['plan_id'])) {
                $defaultPlan = \App\Models\Content\ContentPlan::firstOrCreate(
                    [
                        'org_id' => $orgId,
                        'name' => 'Default Content Plan',
                    ],
                    [
                        'plan_id' => \Illuminate\Support\Str::uuid()->toString(),
                        'status' => 'active',
                        'created_by' => $request->user()->user_id,
                    ]
                );
                $validated['plan_id'] = $defaultPlan->plan_id;
            }

            $content = ContentPlanItem::create($validated);

            return response()->json([
                'data' => $content,
                'success' => true,
                'message' => 'Content created successfully',
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Content creation error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified content item.
     */
    public function show(Request $request, string $contentId)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            $content = ContentPlanItem::where('org_id', $orgId)
                ->where('item_id', $contentId)
                ->first();

            if (!$content) {
                // Check if content exists in another org
                $existsInOtherOrg = ContentPlanItem::where('item_id', $contentId)->exists();

                if ($existsInOtherOrg) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to content',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Content not found',
                ], 404);
            }

            return response()->json([
                'data' => $content,
                'success' => true,
            ]);

        } catch (\Exception $e) {
            \Log::error('Content show error', [
                'content_id' => $contentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified content item.
     */
    public function update(Request $request, string $contentId)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            $content = ContentPlanItem::where('org_id', $orgId)
                ->where('item_id', $contentId)
                ->first();

            if (!$content) {
                // Check if content exists in another org
                $existsInOtherOrg = ContentPlanItem::where('item_id', $contentId)->exists();

                if ($existsInOtherOrg) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to content',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Content not found',
                ], 404);
            }

            // Validate request
            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'body' => ['nullable', 'string'],
                'content_type' => ['nullable', 'string'],
                'platform' => ['nullable', 'string'],
                'status' => ['sometimes', 'string', 'in:draft,scheduled,published,archived'],
                'scheduled_at' => ['nullable', 'date'],
            ]);

            $content->update($validated);

            return response()->json([
                'data' => $content->fresh(),
                'success' => true,
                'message' => 'Content updated successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Content update error', [
                'content_id' => $contentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified content item.
     */
    public function destroy(Request $request, string $contentId)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            $content = ContentPlanItem::where('org_id', $orgId)
                ->where('item_id', $contentId)
                ->first();

            if (!$content) {
                // Check if content exists in another org
                $existsInOtherOrg = ContentPlanItem::where('item_id', $contentId)->exists();

                if ($existsInOtherOrg) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to content',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Content not found',
                ], 404);
            }

            // Soft delete the content
            $content->delete();

            return response()->json([
                'success' => true,
                'message' => 'Content deleted successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Content delete error', [
                'content_id' => $contentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule the specified content item.
     */
    public function schedule(Request $request, string $contentId)
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'No organization context found',
                ], 400);
            }

            $content = ContentPlanItem::where('org_id', $orgId)
                ->where('item_id', $contentId)
                ->first();

            if (!$content) {
                // Check if content exists in another org
                $existsInOtherOrg = ContentPlanItem::where('item_id', $contentId)->exists();

                if ($existsInOtherOrg) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Unauthorized access to content',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Content not found',
                ], 404);
            }

            // Validate request
            $validated = $request->validate([
                'scheduled_at' => ['required', 'date', 'after:now'],
            ]);

            $content->update([
                'scheduled_at' => $validated['scheduled_at'],
                'status' => 'scheduled',
            ]);

            return response()->json([
                'data' => $content->fresh(),
                'success' => true,
                'message' => 'Content scheduled successfully',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Content schedule error', [
                'content_id' => $contentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to schedule content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish the specified content item.
     */
    public function publish(Request $request, $content_id)
    {
        // TODO: Implement content publishing logic
        return response()->json([
            'message' => 'Content publish endpoint - implementation pending',
            'content_id' => $content_id
        ]);
    }

    /**
     * Unpublish the specified content item.
     */
    public function unpublish(Request $request, $content_id)
    {
        // TODO: Implement content unpublishing logic
        return response()->json([
            'message' => 'Content unpublish endpoint - implementation pending',
            'content_id' => $content_id
        ]);
    }

    /**
     * Get content versions.
     */
    public function versions(Request $request, $content_id)
    {
        // TODO: Implement content versioning
        return response()->json([
            'message' => 'Content versions endpoint - implementation pending',
            'content_id' => $content_id,
            'versions' => []
        ]);
    }

    /**
     * Resolve org_id from request context
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

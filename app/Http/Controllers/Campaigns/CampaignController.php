<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Campaign\CampaignCollection;
use App\Http\Resources\Campaign\CampaignDetailResource;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Campaign;
use App\Models\Core\Org;
use App\Services\CampaignService;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index(Request $request)
    {
        try {
            // Get org_id from session or user's first organization
            $orgId = session('current_org_id');

            if (!$orgId) {
                // Try to get from user's organizations
                $user = auth()->user();
                if (!$user) {
                    return redirect()->route('login');
                }

                $firstOrg = $user->orgs()->first();

                if (!$firstOrg) {
                    $firstOrg = Org::orderBy('created_at')->first();
                }

                if ($firstOrg) {
                    $orgId = $firstOrg->org_id;
                    session(['current_org_id' => $orgId]);
                } else {
                    return redirect()->route('dashboard')
                        ->with('error', 'يجب أن تكون عضواً في منظمة للوصول إلى الحملات');
                }
            }

            // Get validated data or use defaults
            $validated = $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
                'campaign_type' => ['sometimes', 'string'],
                'search' => ['sometimes', 'string', 'max:255'],
                'sort_by' => ['sometimes', 'string'],
                'sort_direction' => ['sometimes', 'string', 'in:asc,desc'],
            ]);

            $query = Campaign::where('org_id', $orgId);

            // Apply filters
            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['campaign_type'])) {
                $query->where('campaign_type', $validated['campaign_type']);
            }

            if (!empty($validated['search'])) {
                $query->where('name', 'ilike', "%{$validated['search']}%");
            }

            if (!empty($validated['start_date_from'])) {
                $query->where('start_date', '>=', $validated['start_date_from']);
            }

            if (!empty($validated['start_date_to'])) {
                $query->where('start_date', '<=', $validated['start_date_to']);
            }

            if (!empty($validated['budget_min'])) {
                $query->where('budget', '>=', $validated['budget_min']);
            }

            if (!empty($validated['budget_max'])) {
                $query->where('budget', '<=', $validated['budget_max']);
            }

            if (!empty($validated['created_by'])) {
                $query->where('created_by', $validated['created_by']);
            }

            // Sorting
            $query->orderBy(
                $validated['sort_by'] ?? 'created_at',
                $validated['sort_direction'] ?? 'desc'
            );

            // Eager load relationships to prevent N+1
            $query->with(['org', 'creator']);

            // Pagination
            $campaigns = $query->paginate($validated['per_page'] ?? 20);

            // Return view for web requests, JSON for API requests
            if ($request->expectsJson()) {
                return new CampaignCollection($campaigns);
            }

            return view('campaigns.index', compact('campaigns'));

        } catch (\Exception $e) {
            \Log::error('Campaigns index error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'فشل جلب الحملات',
                    'message' => $e->getMessage()
                ], 500);
            }

            return redirect()->route('dashboard')
                ->with('error', 'فشل جلب الحملات: ' . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        return view('campaigns.create');
    }

    public function store(StoreCampaignRequest $request)
    {
        try {
            $validated = $request->validated();

            $campaign = $this->campaignService->create($validated);

            return (new CampaignResource($campaign))
                ->additional([
                    'success' => true,
                    'message' => 'تم إنشاء الحملة بنجاح',
                ])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل إنشاء الحملة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, string $campaignId)
    {
        try {
            $orgId = session('current_org_id');

            if (!$orgId) {
                $user = auth()->user();
                if (!$user) {
                    return redirect()->route('login');
                }

                $firstOrg = $user->orgs()->first();
                if ($firstOrg) {
                    $orgId = $firstOrg->org_id;
                    session(['current_org_id' => $orgId]);
                } else {
                    return redirect()->route('dashboard')
                        ->with('error', 'يجب أن تكون عضواً في منظمة');
                }
            }

            $campaign = Campaign::where('org_id', $orgId)
                ->with(['creator', 'org', 'offerings', 'performanceMetrics'])
                ->findOrFail($campaignId);

            $this->authorize('view', $campaign);

            // Get related campaigns using service
            $relatedCampaigns = $this->campaignService->findRelatedCampaigns($campaignId, 5);

            return (new CampaignDetailResource($campaign))
                ->additional([
                    'success' => true,
                    'related_campaigns' => $relatedCampaigns,
                ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'لم يتم العثور على الحملة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل جلب الحملة',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, string $campaignId)
    {
        try {
            $orgId = session('current_org_id');

            if (!$orgId) {
                $user = auth()->user();
                if (!$user) {
                    return redirect()->route('login');
                }

                $firstOrg = $user->orgs()->first();
                if ($firstOrg) {
                    $orgId = $firstOrg->org_id;
                    session(['current_org_id' => $orgId]);
                } else {
                    return redirect()->route('dashboard')
                        ->with('error', 'يجب أن تكون عضواً في منظمة');
                }
            }

            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);

            return view('campaigns.edit', compact('campaign'));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('campaigns.index')
                ->with('error', 'لم يتم العثور على الحملة');
        } catch (\Exception $e) {
            return back()->with('error', 'فشل تحميل الحملة: ' . $e->getMessage());
        }
    }

    public function update(UpdateCampaignRequest $request, string $campaignId)
    {
        try {
            $orgId = session('current_org_id');

            if (!$orgId) {
                $user = auth()->user();
                if ($user) {
                    $firstOrg = $user->orgs()->first();
                    if ($firstOrg) {
                        $orgId = $firstOrg->org_id;
                        session(['current_org_id' => $orgId]);
                    }
                }
            }

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'لم يتم تحديد منظمة'
                ], 400);
            }

            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);

            $validated = $request->validated();

            $updatedCampaign = $this->campaignService->update($campaign, $validated);

            return (new CampaignResource($updatedCampaign))
                ->additional([
                    'success' => true,
                    'message' => 'تم تحديث الحملة بنجاح',
                ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'لم يتم العثور على الحملة'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل التحديث',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, string $campaignId)
    {
        try {
            $orgId = session('current_org_id');

            if (!$orgId) {
                $user = auth()->user();
                if ($user) {
                    $firstOrg = $user->orgs()->first();
                    if ($firstOrg) {
                        $orgId = $firstOrg->org_id;
                        session(['current_org_id' => $orgId]);
                    }
                }
            }

            if (!$orgId) {
                return response()->json([
                    'success' => false,
                    'error' => 'لم يتم تحديد منظمة'
                ], 400);
            }

            $campaign = Campaign::where('org_id', $orgId)->findOrFail($campaignId);
            $this->authorize('delete', $campaign);

            $deleted = $this->campaignService->delete($campaign);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الحملة بنجاح'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'فشل حذف الحملة'
            ], 500);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'لم يتم العثور على الحملة'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'غير مصرح لك بحذف هذه الحملة'
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'فشل الحذف',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

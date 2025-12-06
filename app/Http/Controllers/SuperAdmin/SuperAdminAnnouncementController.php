<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Core\Announcement;
use App\Models\Subscription\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Super Admin Announcement Controller
 *
 * Manages platform-wide announcements and broadcasts.
 */
class SuperAdminAnnouncementController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display a listing of announcements.
     */
    public function index(Request $request)
    {
        $query = Announcement::query()->withCount(['views', 'dismissals']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'scheduled':
                    $query->scheduled();
                    break;
                case 'expired':
                    $query->expired();
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortField, $sortDir);

        $announcements = $query->paginate(15)->withQueryString();

        // Get stats
        $stats = [
            'total' => Announcement::count(),
            'active' => Announcement::active()->count(),
            'scheduled' => Announcement::scheduled()->count(),
            'critical' => Announcement::where('type', 'critical')->active()->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'announcements' => $announcements,
                'stats' => $stats,
            ]);
        }

        return view('super-admin.announcements.index', compact('announcements', 'stats'));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create()
    {
        $plans = Plan::select('plan_id', 'name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $organizations = DB::table('cmis.orgs')
            ->select('org_id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('super-admin.announcements.create', compact('plans', 'organizations'));
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'priority' => ['required', Rule::in(array_keys(Announcement::PRIORITIES))],
            'target_audience' => ['required', Rule::in(array_keys(Announcement::TARGET_AUDIENCES))],
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'uuid',
            'is_dismissible' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|url|max:500',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['created_by'] = Auth::id();
            $validated['is_dismissible'] = $request->boolean('is_dismissible', true);
            $validated['is_active'] = $request->boolean('is_active', true);

            $announcement = Announcement::create($validated);

            $this->logAction('announcement_created', 'announcement', $announcement->announcement_id, $announcement->title, [
                'type' => $announcement->type,
                'priority' => $announcement->priority,
                'target_audience' => $announcement->target_audience,
            ]);

            if ($request->expectsJson()) {
                return $this->created($announcement, __('super_admin.announcements.created_success'));
            }

            return redirect()
                ->route('super-admin.announcements.index')
                ->with('success', __('super_admin.announcements.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create announcement', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.announcements.create_failed'));
            }

            return back()
                ->withInput()
                ->with('error', __('super_admin.announcements.create_failed'));
        }
    }

    /**
     * Display the specified announcement.
     */
    public function show(string $announcementId)
    {
        $announcement = Announcement::with('creator')
            ->withCount(['views', 'dismissals'])
            ->findOrFail($announcementId);

        // Get view analytics
        $viewsByDay = DB::table('cmis.announcement_views')
            ->where('announcement_id', $announcementId)
            ->where('viewed_at', '>=', now()->subDays(30))
            ->select([
                DB::raw("DATE(viewed_at) as date"),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT user_id) as unique_viewers'),
            ])
            ->groupBy(DB::raw("DATE(viewed_at)"))
            ->orderBy('date')
            ->get();

        // Get unique viewer count
        $uniqueViewers = DB::table('cmis.announcement_views')
            ->where('announcement_id', $announcementId)
            ->distinct('user_id')
            ->count('user_id');

        // Get recent views
        $recentViews = DB::table('cmis.announcement_views as av')
            ->join('cmis.users as u', 'av.user_id', '=', 'u.user_id')
            ->leftJoin('cmis.orgs as o', 'av.org_id', '=', 'o.org_id')
            ->where('av.announcement_id', $announcementId)
            ->select([
                'av.*',
                'u.name as user_name',
                'u.email as user_email',
                'o.name as org_name',
            ])
            ->orderByDesc('av.viewed_at')
            ->limit(20)
            ->get();

        return view('super-admin.announcements.show', compact(
            'announcement',
            'viewsByDay',
            'uniqueViewers',
            'recentViews'
        ));
    }

    /**
     * Show the form for editing an announcement.
     */
    public function edit(string $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);

        $plans = Plan::select('plan_id', 'name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $organizations = DB::table('cmis.orgs')
            ->select('org_id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('super-admin.announcements.edit', compact('announcement', 'plans', 'organizations'));
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, string $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => ['required', Rule::in(array_keys(Announcement::TYPES))],
            'priority' => ['required', Rule::in(array_keys(Announcement::PRIORITIES))],
            'target_audience' => ['required', Rule::in(array_keys(Announcement::TARGET_AUDIENCES))],
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'uuid',
            'is_dismissible' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|url|max:500',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['is_dismissible'] = $request->boolean('is_dismissible', true);
            $validated['is_active'] = $request->boolean('is_active', true);

            $announcement->update($validated);

            $this->logAction('announcement_updated', 'announcement', $announcement->announcement_id, $announcement->title, [
                'type' => $announcement->type,
                'priority' => $announcement->priority,
            ]);

            if ($request->expectsJson()) {
                return $this->success($announcement, __('super_admin.announcements.updated_success'));
            }

            return redirect()
                ->route('super-admin.announcements.show', $announcement->announcement_id)
                ->with('success', __('super_admin.announcements.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update announcement', [
                'announcement_id' => $announcementId,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return $this->serverError(__('super_admin.announcements.update_failed'));
            }

            return back()
                ->withInput()
                ->with('error', __('super_admin.announcements.update_failed'));
        }
    }

    /**
     * Toggle announcement active status.
     */
    public function toggleActive(Request $request, string $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);

        $announcement->is_active = !$announcement->is_active;
        $announcement->save();

        $action = $announcement->is_active ? 'announcement_activated' : 'announcement_deactivated';
        $this->logAction($action, 'announcement', $announcement->announcement_id, $announcement->title);

        return $this->success([
            'is_active' => $announcement->is_active,
        ], $announcement->is_active
            ? __('super_admin.announcements.activated_success')
            : __('super_admin.announcements.deactivated_success')
        );
    }

    /**
     * Duplicate an announcement.
     */
    public function duplicate(string $announcementId)
    {
        $original = Announcement::findOrFail($announcementId);

        $copy = $original->replicate();
        $copy->title = $original->title . ' (Copy)';
        $copy->is_active = false;
        $copy->created_by = Auth::id();
        $copy->save();

        $this->logAction('announcement_duplicated', 'announcement', $copy->announcement_id, $copy->title, [
            'original_id' => $original->announcement_id,
        ]);

        return redirect()
            ->route('super-admin.announcements.edit', $copy->announcement_id)
            ->with('success', __('super_admin.announcements.duplicated_success'));
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(string $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);

        $this->logAction('announcement_deleted', 'announcement', $announcement->announcement_id, $announcement->title);

        $announcement->delete();

        return redirect()
            ->route('super-admin.announcements.index')
            ->with('success', __('super_admin.announcements.deleted_success'));
    }

    /**
     * Get active announcements for the current user (API endpoint for frontend).
     */
    public function getActiveForUser(Request $request)
    {
        $user = Auth::user();
        $orgId = $request->header('X-Org-Id') ?? session('current_org_id');

        // Get user's plan from their organization
        $planId = null;
        if ($orgId) {
            $planId = DB::table('cmis.org_subscriptions')
                ->where('org_id', $orgId)
                ->where('status', 'active')
                ->value('plan_id');
        }

        // Get all active announcements
        $announcements = Announcement::active()
            ->orderBy('priority', 'desc')
            ->orderByDesc('created_at')
            ->get();

        // Filter by visibility and dismissal status
        $visibleAnnouncements = $announcements->filter(function ($announcement) use ($user, $orgId, $planId) {
            if (!$announcement->isVisibleToUser($user, $orgId, $planId)) {
                return false;
            }

            // Check if dismissed
            if ($announcement->is_dismissible && $announcement->isDismissedByUser($user->user_id)) {
                return false;
            }

            return true;
        })->values();

        // Record views
        foreach ($visibleAnnouncements as $announcement) {
            try {
                DB::table('cmis.announcement_views')->insert([
                    'view_id' => \Illuminate\Support\Str::uuid(),
                    'announcement_id' => $announcement->announcement_id,
                    'user_id' => $user->user_id,
                    'org_id' => $orgId,
                    'viewed_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Ignore duplicate view errors
            }
        }

        return $this->success([
            'announcements' => $visibleAnnouncements,
        ]);
    }

    /**
     * Dismiss an announcement for the current user.
     */
    public function dismiss(Request $request, string $announcementId)
    {
        $announcement = Announcement::findOrFail($announcementId);

        if (!$announcement->is_dismissible) {
            return $this->error(__('super_admin.announcements.not_dismissible'), 400);
        }

        $userId = Auth::id();

        // Check if already dismissed
        $exists = DB::table('cmis.announcement_dismissals')
            ->where('announcement_id', $announcementId)
            ->where('user_id', $userId)
            ->exists();

        if (!$exists) {
            DB::table('cmis.announcement_dismissals')->insert([
                'dismissal_id' => \Illuminate\Support\Str::uuid(),
                'announcement_id' => $announcementId,
                'user_id' => $userId,
                'dismissed_at' => now(),
            ]);
        }

        return $this->success([
            'dismissed' => true,
        ], __('super_admin.announcements.dismissed_success'));
    }
}

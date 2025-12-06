<?php

namespace App\Http\Controllers\SuperAdmin\Website;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Website\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Team Controller
 *
 * Manages team members for the About page.
 */
class SuperAdminTeamController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    public function index(Request $request)
    {
        $query = TeamMember::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('department')) {
            $query->where('department_en', $request->department);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'ilike', "%{$search}%")
                  ->orWhere('role_en', 'ilike', "%{$search}%");
            });
        }

        $members = $query->orderBy('sort_order')->paginate(15)->withQueryString();

        $departments = TeamMember::distinct()->pluck('department_en')->filter();

        if ($request->expectsJson()) {
            return $this->success(['members' => $members, 'departments' => $departments]);
        }

        return view('super-admin.website.team.index', compact('members', 'departments'));
    }

    public function create()
    {
        $departments = TeamMember::distinct()->pluck('department_en')->filter();
        return view('super-admin.website.team.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'role_en' => 'required|string|max:255',
            'role_ar' => 'nullable|string|max:255',
            'department_en' => 'nullable|string|max:255',
            'department_ar' => 'nullable|string|max:255',
            'bio_en' => 'nullable|string|max:2000',
            'bio_ar' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'social_links' => 'nullable|array',
            'social_links.linkedin' => 'nullable|url|max:500',
            'social_links.twitter' => 'nullable|url|max:500',
            'social_links.facebook' => 'nullable|url|max:500',
            'is_active' => 'boolean',
            'is_leadership' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_leadership'] = $request->boolean('is_leadership', false);
            $validated['sort_order'] = $validated['sort_order'] ?? TeamMember::max('sort_order') + 1;

            $member = TeamMember::create($validated);

            $this->logAction('team_member_created', 'team_member', $member->id, $member->name_en);

            if ($request->expectsJson()) {
                return $this->created($member, __('super_admin.website.team.created_success'));
            }

            return redirect()
                ->route('super-admin.website.team.index')
                ->with('success', __('super_admin.website.team.created_success'));
        } catch (\Exception $e) {
            Log::error('Failed to create team member', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.team.create_failed'));
        }
    }

    public function edit(string $id)
    {
        $member = TeamMember::findOrFail($id);
        $departments = TeamMember::distinct()->pluck('department_en')->filter();
        return view('super-admin.website.team.edit', compact('member', 'departments'));
    }

    public function update(Request $request, string $id)
    {
        $member = TeamMember::findOrFail($id);

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'role_en' => 'required|string|max:255',
            'role_ar' => 'nullable|string|max:255',
            'department_en' => 'nullable|string|max:255',
            'department_ar' => 'nullable|string|max:255',
            'bio_en' => 'nullable|string|max:2000',
            'bio_ar' => 'nullable|string|max:2000',
            'image_url' => 'nullable|url|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'social_links' => 'nullable|array',
            'is_active' => 'boolean',
            'is_leadership' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['is_leadership'] = $request->boolean('is_leadership', false);
            $member->update($validated);

            $this->logAction('team_member_updated', 'team_member', $member->id, $member->name_en);

            if ($request->expectsJson()) {
                return $this->success($member, __('super_admin.website.team.updated_success'));
            }

            return redirect()
                ->route('super-admin.website.team.index')
                ->with('success', __('super_admin.website.team.updated_success'));
        } catch (\Exception $e) {
            Log::error('Failed to update team member', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', __('super_admin.website.team.update_failed'));
        }
    }

    public function toggleActive(string $id)
    {
        $member = TeamMember::findOrFail($id);
        $member->is_active = !$member->is_active;
        $member->save();

        return $this->success(['is_active' => $member->is_active]);
    }

    public function destroy(string $id)
    {
        $member = TeamMember::findOrFail($id);
        $this->logAction('team_member_deleted', 'team_member', $member->id, $member->name_en);
        $member->delete();

        return redirect()
            ->route('super-admin.website.team.index')
            ->with('success', __('super_admin.website.team.deleted_success'));
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            TeamMember::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return $this->success(null, __('super_admin.website.team.reordered_success'));
    }
}

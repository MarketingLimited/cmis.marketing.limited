<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminFeatureFlagController extends Controller
{
    use LogsSuperAdminActions;

    /**
     * Feature Flags Dashboard - Overview
     */
    public function index()
    {
        // Statistics
        $stats = [
            'total_flags' => DB::table('cmis.feature_flags')->whereNull('deleted_at')->count(),
            'active_flags' => DB::table('cmis.feature_flags')
                ->whereNull('deleted_at')
                ->where('value', true)
                ->count(),
            'global_flags' => DB::table('cmis.feature_flags')
                ->whereNull('deleted_at')
                ->where('scope_type', 'global')
                ->count(),
            'org_flags' => DB::table('cmis.feature_flags')
                ->whereNull('deleted_at')
                ->where('scope_type', 'organization')
                ->count(),
        ];

        // Flags by scope
        $flagsByScope = DB::table('cmis.feature_flags')
            ->whereNull('deleted_at')
            ->select('scope_type', DB::raw('COUNT(*) as count'))
            ->groupBy('scope_type')
            ->get();

        // Recent flags
        $recentFlags = DB::table('cmis.feature_flags as f')
            ->leftJoin('cmis.orgs as o', function ($join) {
                $join->on('f.scope_id', '=', 'o.org_id')
                     ->where('f.scope_type', '=', 'organization');
            })
            ->whereNull('f.deleted_at')
            ->select('f.*', 'o.name as org_name')
            ->orderByDesc('f.updated_at')
            ->limit(10)
            ->get();

        // Flags usage audit
        $flagAudit = DB::table('cmis.feature_flag_audit_log')
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as changes')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        return view('super-admin.feature-flags.index', compact(
            'stats',
            'flagsByScope',
            'recentFlags',
            'flagAudit'
        ));
    }

    /**
     * Browse all feature flags
     */
    public function browse(Request $request)
    {
        $query = DB::table('cmis.feature_flags as f')
            ->leftJoin('cmis.orgs as o', function ($join) {
                $join->on('f.scope_id', '=', 'o.org_id')
                     ->where('f.scope_type', '=', 'organization');
            })
            ->whereNull('f.deleted_at')
            ->select('f.*', 'o.name as org_name');

        // Filter by scope type
        if ($request->filled('scope_type')) {
            $query->where('f.scope_type', $request->scope_type);
        }

        // Filter by status
        if ($request->has('value')) {
            $query->where('f.value', $request->value === 'true' || $request->value === '1');
        }

        // Filter by organization
        if ($request->filled('org_id')) {
            $query->where('f.scope_id', $request->org_id);
        }

        // Search by key or description
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('f.feature_key', 'ilike', $search)
                  ->orWhere('f.description', 'ilike', $search);
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'updated_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy("f.$sortField", $sortDir);

        $flags = $query->paginate(20)->withQueryString();

        // Get filter options
        $organizations = Org::select('org_id', 'name')->orderBy('name')->get();
        $scopeTypes = DB::table('cmis.feature_flags')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('scope_type')
            ->filter();

        return view('super-admin.feature-flags.browse', compact('flags', 'organizations', 'scopeTypes'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $organizations = Org::select('org_id', 'name')->orderBy('name')->get();

        return view('super-admin.feature-flags.create', compact('organizations'));
    }

    /**
     * Store a new feature flag
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_key' => 'required|string|max:255|unique:cmis.feature_flags,feature_key',
            'scope_type' => 'required|in:global,organization,user',
            'scope_id' => 'nullable|uuid',
            'value' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ]);

        $flagId = Str::uuid();

        DB::table('cmis.feature_flags')->insert([
            'id' => $flagId,
            'feature_key' => $validated['feature_key'],
            'scope_type' => $validated['scope_type'],
            'scope_id' => $validated['scope_type'] !== 'global' ? $validated['scope_id'] : null,
            'value' => $validated['value'],
            'description' => $validated['description'] ?? null,
            'metadata' => json_encode($validated['metadata'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logAction('feature_flag.created', [
            'flag_id' => $flagId,
            'feature_key' => $validated['feature_key'],
            'scope_type' => $validated['scope_type'],
            'value' => $validated['value'],
        ]);

        // Log to audit table
        DB::table('cmis.feature_flag_audit_log')->insert([
            'id' => Str::uuid(),
            'flag_id' => $flagId,
            'action' => 'created',
            'old_value' => null,
            'new_value' => json_encode($validated),
            'performed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('super-admin.feature-flags.index')
            ->with('success', __('super_admin.feature_flags.created_success'));
    }

    /**
     * Show feature flag details
     */
    public function show($flagId)
    {
        $flag = DB::table('cmis.feature_flags as f')
            ->leftJoin('cmis.orgs as o', function ($join) {
                $join->on('f.scope_id', '=', 'o.org_id')
                     ->where('f.scope_type', '=', 'organization');
            })
            ->where('f.id', $flagId)
            ->select('f.*', 'o.name as org_name')
            ->first();

        if (!$flag) {
            abort(404, 'Feature flag not found');
        }

        // Get audit history
        $auditHistory = DB::table('cmis.feature_flag_audit_log as a')
            ->leftJoin('cmis.users as u', 'a.performed_by', '=', 'u.user_id')
            ->where('a.flag_id', $flagId)
            ->select('a.*', 'u.name as performer_name')
            ->orderByDesc('a.created_at')
            ->limit(20)
            ->get();

        // Get overrides
        $overrides = DB::table('cmis.feature_flag_overrides as o')
            ->leftJoin('cmis.orgs as org', function ($join) {
                $join->on('o.scope_id', '=', 'org.org_id')
                     ->where('o.scope_type', '=', 'organization');
            })
            ->leftJoin('cmis.users as u', function ($join) {
                $join->on('o.scope_id', '=', 'u.user_id')
                     ->where('o.scope_type', '=', 'user');
            })
            ->where('o.flag_id', $flagId)
            ->select('o.*', 'org.name as org_name', 'u.name as user_name')
            ->orderByDesc('o.created_at')
            ->get();

        return view('super-admin.feature-flags.show', compact('flag', 'auditHistory', 'overrides'));
    }

    /**
     * Show edit form
     */
    public function edit($flagId)
    {
        $flag = DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->whereNull('deleted_at')
            ->first();

        if (!$flag) {
            abort(404, 'Feature flag not found');
        }

        $organizations = Org::select('org_id', 'name')->orderBy('name')->get();

        return view('super-admin.feature-flags.edit', compact('flag', 'organizations'));
    }

    /**
     * Update a feature flag
     */
    public function update(Request $request, $flagId)
    {
        $flag = DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->whereNull('deleted_at')
            ->first();

        if (!$flag) {
            abort(404, 'Feature flag not found');
        }

        $validated = $request->validate([
            'scope_type' => 'required|in:global,organization,user',
            'scope_id' => 'nullable|uuid',
            'value' => 'required|boolean',
            'description' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ]);

        $oldValue = [
            'scope_type' => $flag->scope_type,
            'scope_id' => $flag->scope_id,
            'value' => $flag->value,
            'description' => $flag->description,
        ];

        DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->update([
                'scope_type' => $validated['scope_type'],
                'scope_id' => $validated['scope_type'] !== 'global' ? $validated['scope_id'] : null,
                'value' => $validated['value'],
                'description' => $validated['description'] ?? null,
                'metadata' => json_encode($validated['metadata'] ?? []),
                'updated_at' => now(),
            ]);

        $this->logAction('feature_flag.updated', [
            'flag_id' => $flagId,
            'feature_key' => $flag->feature_key,
            'old_value' => $oldValue,
            'new_value' => $validated,
        ]);

        // Log to audit table
        DB::table('cmis.feature_flag_audit_log')->insert([
            'id' => Str::uuid(),
            'flag_id' => $flagId,
            'action' => 'updated',
            'old_value' => json_encode($oldValue),
            'new_value' => json_encode($validated),
            'performed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('super-admin.feature-flags.show', $flagId)
            ->with('success', __('super_admin.feature_flags.updated_success'));
    }

    /**
     * Toggle flag value
     */
    public function toggle(Request $request, $flagId)
    {
        $flag = DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->whereNull('deleted_at')
            ->first();

        if (!$flag) {
            return response()->json([
                'success' => false,
                'message' => __('super_admin.feature_flags.not_found')
            ], 404);
        }

        $newValue = !$flag->value;

        DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->update([
                'value' => $newValue,
                'updated_at' => now(),
            ]);

        $this->logAction('feature_flag.toggled', [
            'flag_id' => $flagId,
            'feature_key' => $flag->feature_key,
            'old_value' => $flag->value,
            'new_value' => $newValue,
        ]);

        // Log to audit table
        DB::table('cmis.feature_flag_audit_log')->insert([
            'id' => Str::uuid(),
            'flag_id' => $flagId,
            'action' => 'toggled',
            'old_value' => json_encode(['value' => $flag->value]),
            'new_value' => json_encode(['value' => $newValue]),
            'performed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'value' => $newValue,
            'message' => __('super_admin.feature_flags.toggled_success')
        ]);
    }

    /**
     * Delete a feature flag
     */
    public function destroy($flagId)
    {
        $flag = DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->whereNull('deleted_at')
            ->first();

        if (!$flag) {
            return response()->json([
                'success' => false,
                'message' => __('super_admin.feature_flags.not_found')
            ], 404);
        }

        DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->update(['deleted_at' => now()]);

        $this->logAction('feature_flag.deleted', [
            'flag_id' => $flagId,
            'feature_key' => $flag->feature_key,
        ]);

        // Log to audit table
        DB::table('cmis.feature_flag_audit_log')->insert([
            'id' => Str::uuid(),
            'flag_id' => $flagId,
            'action' => 'deleted',
            'old_value' => json_encode(['feature_key' => $flag->feature_key]),
            'new_value' => null,
            'performed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('super_admin.feature_flags.deleted_success')
        ]);
    }

    /**
     * Add override for specific org or user
     */
    public function addOverride(Request $request, $flagId)
    {
        $flag = DB::table('cmis.feature_flags')
            ->where('id', $flagId)
            ->whereNull('deleted_at')
            ->first();

        if (!$flag) {
            return response()->json([
                'success' => false,
                'message' => __('super_admin.feature_flags.not_found')
            ], 404);
        }

        $validated = $request->validate([
            'scope_type' => 'required|in:organization,user',
            'scope_id' => 'required|uuid',
            'value' => 'required|boolean',
        ]);

        $overrideId = Str::uuid();

        DB::table('cmis.feature_flag_overrides')->insert([
            'id' => $overrideId,
            'flag_id' => $flagId,
            'scope_type' => $validated['scope_type'],
            'scope_id' => $validated['scope_id'],
            'value' => $validated['value'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logAction('feature_flag.override_added', [
            'flag_id' => $flagId,
            'feature_key' => $flag->feature_key,
            'override' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'override_id' => $overrideId,
            'message' => __('super_admin.feature_flags.override_added')
        ]);
    }

    /**
     * Remove override
     */
    public function removeOverride($flagId, $overrideId)
    {
        $override = DB::table('cmis.feature_flag_overrides')
            ->where('id', $overrideId)
            ->where('flag_id', $flagId)
            ->first();

        if (!$override) {
            return response()->json([
                'success' => false,
                'message' => __('super_admin.feature_flags.override_not_found')
            ], 404);
        }

        DB::table('cmis.feature_flag_overrides')
            ->where('id', $overrideId)
            ->delete();

        $this->logAction('feature_flag.override_removed', [
            'flag_id' => $flagId,
            'override_id' => $overrideId,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('super_admin.feature_flags.override_removed')
        ]);
    }

    /**
     * Bulk toggle flags
     */
    public function bulkToggle(Request $request)
    {
        $validated = $request->validate([
            'flag_ids' => 'required|array',
            'flag_ids.*' => 'uuid',
            'value' => 'required|boolean',
        ]);

        $count = DB::table('cmis.feature_flags')
            ->whereIn('id', $validated['flag_ids'])
            ->whereNull('deleted_at')
            ->update([
                'value' => $validated['value'],
                'updated_at' => now(),
            ]);

        $this->logAction('feature_flags.bulk_toggled', [
            'flag_ids' => $validated['flag_ids'],
            'value' => $validated['value'],
            'count' => $count,
        ]);

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => __('super_admin.feature_flags.bulk_toggled', ['count' => $count])
        ]);
    }
}

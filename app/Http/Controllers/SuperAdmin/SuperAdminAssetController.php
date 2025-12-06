<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuperAdminAssetController extends Controller
{
    use \App\Http\Controllers\Concerns\LogsSuperAdminActions;

    /**
     * Asset Library Dashboard - Overview of all assets
     */
    public function index()
    {
        // Asset statistics
        $stats = [
            'total_assets' => DB::table('cmis.assets')->whereNull('deleted_at')->count(),
            'total_storage' => DB::table('cmis.assets')->whereNull('deleted_at')->sum('size'),
            'organizations_with_assets' => DB::table('cmis.assets')
                ->whereNull('deleted_at')
                ->distinct('org_id')
                ->count('org_id'),
            'unused_assets' => DB::table('cmis.assets')
                ->whereNull('deleted_at')
                ->where('usage_count', 0)
                ->count(),
        ];

        // Assets by type
        $assetsByType = DB::table('cmis.assets')
            ->whereNull('deleted_at')
            ->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(size) as total_size'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        // Storage usage by organization (top 10)
        $storageByOrg = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->select(
                'o.org_id',
                'o.name as org_name',
                DB::raw('COUNT(*) as asset_count'),
                DB::raw('SUM(a.size) as total_size')
            )
            ->groupBy('o.org_id', 'o.name')
            ->orderByDesc('total_size')
            ->limit(10)
            ->get();

        // Recent assets
        $recentAssets = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->select('a.*', 'o.name as org_name')
            ->orderByDesc('a.created_at')
            ->limit(10)
            ->get();

        // Large assets (>10MB)
        $largeAssets = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->where('a.size', '>', 10 * 1024 * 1024) // 10MB
            ->select('a.*', 'o.name as org_name')
            ->orderByDesc('a.size')
            ->limit(10)
            ->get();

        return view('super-admin.assets.index', compact(
            'stats',
            'assetsByType',
            'storageByOrg',
            'recentAssets',
            'largeAssets'
        ));
    }

    /**
     * Browse all assets with filtering
     */
    public function browse(Request $request)
    {
        $query = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->select('a.*', 'o.name as org_name');

        // Filter by organization
        if ($request->filled('org_id')) {
            $query->where('a.org_id', $request->org_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('a.type', $request->type);
        }

        // Filter by usage
        if ($request->filled('usage')) {
            if ($request->usage === 'unused') {
                $query->where('a.usage_count', 0);
            } elseif ($request->usage === 'used') {
                $query->where('a.usage_count', '>', 0);
            }
        }

        // Filter by size range
        if ($request->filled('size_min')) {
            $query->where('a.size', '>=', $request->size_min * 1024 * 1024); // MB
        }
        if ($request->filled('size_max')) {
            $query->where('a.size', '<=', $request->size_max * 1024 * 1024); // MB
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('a.name', 'ilike', '%' . $request->search . '%');
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy("a.$sortField", $sortDir);

        $assets = $query->paginate(24)->withQueryString();

        // Get filter options
        $organizations = Org::select('org_id', 'name')->orderBy('name')->get();
        $types = DB::table('cmis.assets')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('type')
            ->filter();

        return view('super-admin.assets.browse', compact('assets', 'organizations', 'types'));
    }

    /**
     * Show asset details
     */
    public function show($assetId)
    {
        $asset = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->where('a.asset_id', $assetId)
            ->select('a.*', 'o.name as org_name')
            ->first();

        if (!$asset) {
            abort(404, 'Asset not found');
        }

        // Find usage in different contexts
        $usageLocations = [];

        // Check creative assets
        $creativeUsage = DB::table('cmis.creative_assets')
            ->where('asset_id', $assetId)
            ->whereNull('deleted_at')
            ->count();
        if ($creativeUsage > 0) {
            $usageLocations[] = ['type' => 'creative_assets', 'count' => $creativeUsage];
        }

        // Check platform assets
        $platformUsage = DB::table('cmis.platform_assets')
            ->where('asset_id', $assetId)
            ->count();
        if ($platformUsage > 0) {
            $usageLocations[] = ['type' => 'platform_assets', 'count' => $platformUsage];
        }

        // Check media assets
        $mediaUsage = DB::table('cmis.media_assets')
            ->where('asset_id', $assetId)
            ->whereNull('deleted_at')
            ->count();
        if ($mediaUsage > 0) {
            $usageLocations[] = ['type' => 'media_assets', 'count' => $mediaUsage];
        }

        return view('super-admin.assets.show', compact('asset', 'usageLocations'));
    }

    /**
     * Storage usage analytics
     */
    public function storage()
    {
        // Overall storage stats
        $overallStats = [
            'total_size' => DB::table('cmis.assets')->whereNull('deleted_at')->sum('size'),
            'total_assets' => DB::table('cmis.assets')->whereNull('deleted_at')->count(),
            'avg_asset_size' => DB::table('cmis.assets')->whereNull('deleted_at')->avg('size'),
        ];

        // Storage by organization
        $storageByOrg = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->select(
                'o.org_id',
                'o.name as org_name',
                DB::raw('COUNT(*) as asset_count'),
                DB::raw('SUM(a.size) as total_size'),
                DB::raw('AVG(a.size) as avg_size')
            )
            ->groupBy('o.org_id', 'o.name')
            ->orderByDesc('total_size')
            ->get();

        // Storage by type
        $storageByType = DB::table('cmis.assets')
            ->whereNull('deleted_at')
            ->select(
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(size) as total_size'),
                DB::raw('AVG(size) as avg_size')
            )
            ->groupBy('type')
            ->orderByDesc('total_size')
            ->get();

        // Monthly upload trends
        $monthlyTrends = DB::table('cmis.assets')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw("DATE_TRUNC('month', created_at) as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(size) as total_size')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('super-admin.assets.storage', compact(
            'overallStats',
            'storageByOrg',
            'storageByType',
            'monthlyTrends'
        ));
    }

    /**
     * Cleanup utilities - find orphaned or unused assets
     */
    public function cleanup()
    {
        // Find unused assets (usage_count = 0, older than 30 days)
        $unusedAssets = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->where('a.usage_count', 0)
            ->where('a.created_at', '<', now()->subDays(30))
            ->select('a.*', 'o.name as org_name')
            ->orderByDesc('a.size')
            ->limit(100)
            ->get();

        // Calculate potential storage savings
        $potentialSavings = $unusedAssets->sum('size');

        // Soft-deleted assets that can be purged
        $deletedAssets = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNotNull('a.deleted_at')
            ->select('a.*', 'o.name as org_name')
            ->orderByDesc('a.deleted_at')
            ->limit(100)
            ->get();

        // Duplicate detection (same name and size within same org)
        $potentialDuplicates = DB::table('cmis.assets as a')
            ->join('cmis.orgs as o', 'a.org_id', '=', 'o.org_id')
            ->whereNull('a.deleted_at')
            ->select(
                'a.org_id',
                'o.name as org_name',
                'a.name',
                'a.size',
                DB::raw('COUNT(*) as duplicate_count'),
                DB::raw('SUM(a.size) as wasted_space')
            )
            ->groupBy('a.org_id', 'o.name', 'a.name', 'a.size')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('wasted_space')
            ->limit(50)
            ->get();

        return view('super-admin.assets.cleanup', compact(
            'unusedAssets',
            'potentialSavings',
            'deletedAssets',
            'potentialDuplicates'
        ));
    }

    /**
     * Delete an asset
     */
    public function destroy(Request $request, $assetId)
    {
        $asset = DB::table('cmis.assets')
            ->where('asset_id', $assetId)
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => __('super_admin.assets.not_found')
            ], 404);
        }

        // Soft delete
        DB::table('cmis.assets')
            ->where('asset_id', $assetId)
            ->update(['deleted_at' => now()]);

        $this->logAction('asset.deleted', [
            'asset_id' => $assetId,
            'asset_name' => $asset->name,
            'org_id' => $asset->org_id,
            'size' => $asset->size,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('super_admin.assets.deleted_success')
        ]);
    }

    /**
     * Purge soft-deleted assets (permanent delete)
     */
    public function purge(Request $request)
    {
        $olderThan = $request->get('older_than', 30); // days

        $toPurge = DB::table('cmis.assets')
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<', now()->subDays($olderThan));

        $count = $toPurge->count();
        $size = $toPurge->sum('size');

        if ($request->get('confirm') === 'yes') {
            $toPurge->delete();

            $this->logAction('assets.purged', [
                'count' => $count,
                'size' => $size,
                'older_than_days' => $olderThan,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('super_admin.assets.purged_success', ['count' => $count])
            ]);
        }

        return response()->json([
            'success' => true,
            'preview' => true,
            'count' => $count,
            'size' => $size,
            'message' => __('super_admin.assets.purge_preview', ['count' => $count])
        ]);
    }

    /**
     * Bulk delete unused assets
     */
    public function bulkDeleteUnused(Request $request)
    {
        $olderThan = $request->get('older_than', 30); // days
        $maxSize = $request->get('max_size'); // MB, optional

        $query = DB::table('cmis.assets')
            ->whereNull('deleted_at')
            ->where('usage_count', 0)
            ->where('created_at', '<', now()->subDays($olderThan));

        if ($maxSize) {
            $query->where('size', '<=', $maxSize * 1024 * 1024);
        }

        $count = $query->count();
        $size = $query->sum('size');

        if ($request->get('confirm') === 'yes') {
            $query->update(['deleted_at' => now()]);

            $this->logAction('assets.bulk_deleted_unused', [
                'count' => $count,
                'size' => $size,
                'older_than_days' => $olderThan,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('super_admin.assets.bulk_deleted_success', ['count' => $count])
            ]);
        }

        return response()->json([
            'success' => true,
            'preview' => true,
            'count' => $count,
            'size' => $size,
            'message' => __('super_admin.assets.bulk_delete_preview', ['count' => $count])
        ]);
    }
}

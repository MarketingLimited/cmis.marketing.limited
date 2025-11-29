<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catalog Web Controller (Frontend UI)
 *
 * Handles multi-platform product catalog management
 * Integrates with: Meta Catalog, Google Merchant Center, TikTok Catalog, Snapchat Catalog, X Catalog
 */
class CatalogWebController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display catalogs dashboard
     */
    public function index(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403, __('common.no_access_to_organization'));
        }

        $this->setRlsContext($user, $org);

        // Get connected platforms
        $connectedPlatforms = DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $org)
            ->where('is_active', true)
            ->distinct()
            ->pluck('platform')
            ->toArray();

        // Platform catalog configurations
        $platformCatalogs = [
            'meta' => [
                'icon' => 'fa-facebook',
                'color' => 'blue',
                'connected' => in_array('meta', $connectedPlatforms),
                'products_count' => $this->getProductCount($org, 'meta'),
                'last_sync' => $this->getLastSync($org, 'meta'),
            ],
            'google' => [
                'icon' => 'fa-google',
                'color' => 'red',
                'connected' => in_array('google', $connectedPlatforms),
                'products_count' => $this->getProductCount($org, 'google'),
                'last_sync' => $this->getLastSync($org, 'google'),
            ],
            'tiktok' => [
                'icon' => 'fa-tiktok',
                'color' => 'pink',
                'connected' => in_array('tiktok', $connectedPlatforms),
                'products_count' => $this->getProductCount($org, 'tiktok'),
                'last_sync' => $this->getLastSync($org, 'tiktok'),
            ],
            'snapchat' => [
                'icon' => 'fa-snapchat',
                'color' => 'yellow',
                'connected' => in_array('snapchat', $connectedPlatforms),
                'products_count' => $this->getProductCount($org, 'snapchat'),
                'last_sync' => $this->getLastSync($org, 'snapchat'),
            ],
            'twitter' => [
                'icon' => 'fa-x-twitter',
                'color' => 'slate',
                'connected' => in_array('twitter', $connectedPlatforms),
                'products_count' => $this->getProductCount($org, 'twitter'),
                'last_sync' => $this->getLastSync($org, 'twitter'),
            ],
        ];

        // Get all products
        $products = $this->getProducts($org)->paginate(20);

        // Stats
        $stats = [
            'total_products' => $this->getTotalProductCount($org),
            'active_products' => $this->getActiveProductCount($org),
            'synced_products' => $this->getSyncedProductCount($org),
            'pending_sync' => $this->getPendingSyncCount($org),
            'sync_errors' => $this->getSyncErrorCount($org),
        ];

        return view('catalogs.index', compact('orgModel', 'platformCatalogs', 'products', 'stats'));
    }

    /**
     * Show catalog products for a specific platform
     */
    public function show(string $org, string $catalog)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        $products = $this->getProducts($org, $catalog)->paginate(50);

        return view('catalogs.show', compact('orgModel', 'catalog', 'products'));
    }

    /**
     * Display import form
     */
    public function import(string $org)
    {
        $user = auth()->user();
        $orgModel = Org::findOrFail($org);

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        return view('catalogs.import', compact('orgModel'));
    }

    /**
     * Process import
     */
    public function processImport(Request $request, string $org)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'platform' => 'required|in:meta,google,tiktok,snapchat,twitter',
            'import_type' => 'required|in:file,feed_url',
            'file' => 'required_if:import_type,file|file|mimes:csv,xml,json|max:10240',
            'feed_url' => 'required_if:import_type,feed_url|url',
        ]);

        $this->setRlsContext($user, $org);

        // TODO: Process import based on type
        // - Parse file or fetch feed URL
        // - Validate product data
        // - Store in catalog_products table
        // - Queue sync to platform

        return redirect()
            ->route('orgs.catalogs.index', ['org' => $org])
            ->with('success', __('catalogs.import_success', ['count' => 0]));
    }

    /**
     * Sync catalog to platform
     */
    public function sync(Request $request, string $org, string $catalog)
    {
        $user = auth()->user();

        if (!$user->orgs()->where('cmis.orgs.org_id', $org)->exists()) {
            abort(403);
        }

        $this->setRlsContext($user, $org);

        // TODO: Queue sync job for platform
        // - Get platform credentials
        // - Fetch products for platform
        // - Push to platform API

        return back()->with('info', __('catalogs.sync_started'));
    }

    /**
     * Get products query
     */
    private function getProducts(string $org, ?string $platform = null)
    {
        // Try to get products from catalog_products table
        try {
            $query = DB::table('cmis.catalog_products')
                ->where('org_id', $org);

            if ($platform) {
                $query->where('platform', $platform);
            }

            return $query->orderBy('created_at', 'desc');
        } catch (\Exception $e) {
            // Fall back to offerings table
            $query = DB::table('cmis.org_offerings')
                ->where('org_id', $org)
                ->where('kind', 'product');

            return $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Get product count for platform
     */
    private function getProductCount(string $org, string $platform): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->where('platform', $platform)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total product count
     */
    private function getTotalProductCount(string $org): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->count();
        } catch (\Exception $e) {
            return DB::table('cmis.org_offerings')
                ->where('org_id', $org)
                ->where('kind', 'product')
                ->count();
        }
    }

    /**
     * Get active product count
     */
    private function getActiveProductCount(string $org): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->where('status', 'active')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get synced product count
     */
    private function getSyncedProductCount(string $org): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->whereNotNull('platform_id')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get pending sync count
     */
    private function getPendingSyncCount(string $org): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->where('sync_status', 'pending')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get sync error count
     */
    private function getSyncErrorCount(string $org): int
    {
        try {
            return DB::table('cmis.catalog_products')
                ->where('org_id', $org)
                ->where('sync_status', 'error')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get last sync time for platform
     */
    private function getLastSync(string $org, string $platform): ?string
    {
        try {
            $lastSync = DB::table('cmis.catalog_sync_logs')
                ->where('org_id', $org)
                ->where('platform', $platform)
                ->orderBy('synced_at', 'desc')
                ->value('synced_at');

            return $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set RLS context
     */
    private function setRlsContext($user, string $org): void
    {
        try {
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                $user->user_id,
                $org
            ]);
        } catch (\Exception $e) {
            \Log::warning("Could not set RLS context: " . $e->getMessage());
        }
    }
}

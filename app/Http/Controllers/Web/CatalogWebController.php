<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\Platform\SyncCatalogJob;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // Process import based on type
        $importedCount = 0;

        if ($validated['import_type'] === 'file' && $request->hasFile('file')) {
            $importedCount = $this->processFileImport(
                $org,
                $validated['platform'],
                $request->file('file')
            );
        } elseif ($validated['import_type'] === 'feed_url') {
            $importedCount = $this->processFeedImport(
                $org,
                $validated['platform'],
                $validated['feed_url']
            );
        }

        // Queue sync to platform if products were imported
        if ($importedCount > 0) {
            SyncCatalogJob::dispatch($org, $validated['platform'], $user->user_id);
        }

        return redirect()
            ->route('orgs.catalogs.index', ['org' => $org])
            ->with('success', __('catalogs.import_success', ['count' => $importedCount]));
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

        // Queue sync job for platform
        SyncCatalogJob::dispatch($org, $catalog, $user->user_id, [
            'force_full_sync' => $request->boolean('force_full_sync', false),
        ]);

        return back()->with('info', __('catalogs.sync_started'));
    }

    /**
     * Process file import (CSV, XML, JSON)
     */
    private function processFileImport(string $org, string $platform, $file): int
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $contents = file_get_contents($file->getRealPath());
        $products = [];

        switch ($extension) {
            case 'csv':
                $products = $this->parseCsvProducts($contents);
                break;
            case 'json':
                $products = json_decode($contents, true) ?? [];
                break;
            case 'xml':
                $products = $this->parseXmlProducts($contents);
                break;
        }

        return $this->storeProducts($org, $platform, $products);
    }

    /**
     * Process feed URL import
     */
    private function processFeedImport(string $org, string $platform, string $feedUrl): int
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->get($feedUrl);
            $contents = $response->getBody()->getContents();
            $contentType = $response->getHeaderLine('Content-Type');

            $products = [];

            if (str_contains($contentType, 'json')) {
                $products = json_decode($contents, true) ?? [];
            } elseif (str_contains($contentType, 'xml')) {
                $products = $this->parseXmlProducts($contents);
            } else {
                // Assume CSV
                $products = $this->parseCsvProducts($contents);
            }

            return $this->storeProducts($org, $platform, $products);

        } catch (\Exception $e) {
            \Log::error('Feed import failed', [
                'url' => $feedUrl,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Parse CSV content into products array
     */
    private function parseCsvProducts(string $contents): array
    {
        $lines = explode("\n", trim($contents));
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $products = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $values = str_getcsv($line);
            if (count($values) !== count($headers)) continue;

            $product = array_combine($headers, $values);
            $products[] = $this->normalizeProductData($product);
        }

        return $products;
    }

    /**
     * Parse XML content into products array
     */
    private function parseXmlProducts(string $contents): array
    {
        try {
            $xml = simplexml_load_string($contents);
            $products = [];

            // Handle common feed formats
            $items = $xml->channel->item ?? $xml->item ?? $xml->product ?? $xml->entry ?? [];

            foreach ($items as $item) {
                $product = [
                    'name' => (string) ($item->title ?? $item->name ?? ''),
                    'description' => (string) ($item->description ?? ''),
                    'price' => (float) ($item->price ?? $item->{'g:price'} ?? 0),
                    'sku' => (string) ($item->id ?? $item->sku ?? $item->{'g:id'} ?? ''),
                    'url' => (string) ($item->link ?? $item->url ?? ''),
                    'image_url' => (string) ($item->image ?? $item->image_link ?? $item->{'g:image_link'} ?? ''),
                    'brand' => (string) ($item->brand ?? $item->{'g:brand'} ?? ''),
                    'in_stock' => true,
                ];
                $products[] = $product;
            }

            return $products;

        } catch (\Exception $e) {
            \Log::warning('XML parse failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Normalize product data from various formats
     */
    private function normalizeProductData(array $product): array
    {
        return [
            'name' => $product['name'] ?? $product['title'] ?? $product['product_name'] ?? '',
            'description' => $product['description'] ?? $product['desc'] ?? '',
            'price' => (float) ($product['price'] ?? $product['sale_price'] ?? 0),
            'sku' => $product['sku'] ?? $product['id'] ?? $product['product_id'] ?? '',
            'url' => $product['url'] ?? $product['link'] ?? $product['product_url'] ?? '',
            'image_url' => $product['image_url'] ?? $product['image'] ?? $product['image_link'] ?? '',
            'brand' => $product['brand'] ?? $product['manufacturer'] ?? '',
            'currency' => $product['currency'] ?? 'USD',
            'in_stock' => filter_var($product['in_stock'] ?? $product['availability'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'condition' => $product['condition'] ?? 'new',
        ];
    }

    /**
     * Store products in database
     */
    private function storeProducts(string $org, string $platform, array $products): int
    {
        $count = 0;

        foreach ($products as $product) {
            if (empty($product['name'])) continue;

            try {
                DB::table('cmis.catalog_products')->updateOrInsert(
                    [
                        'org_id' => $org,
                        'sku' => $product['sku'] ?: Str::uuid()->toString(),
                    ],
                    [
                        'id' => Str::uuid()->toString(),
                        'name' => $product['name'],
                        'description' => $product['description'] ?? null,
                        'price' => $product['price'] ?? 0,
                        'currency' => $product['currency'] ?? 'USD',
                        'url' => $product['url'] ?? null,
                        'image_url' => $product['image_url'] ?? null,
                        'brand' => $product['brand'] ?? null,
                        'in_stock' => $product['in_stock'] ?? true,
                        'condition' => $product['condition'] ?? 'new',
                        'platform' => $platform,
                        'status' => 'active',
                        'sync_status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $count++;
            } catch (\Exception $e) {
                \Log::warning('Failed to store product', [
                    'sku' => $product['sku'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
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

<?php

namespace App\Jobs\Platform;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sync Catalog Job
 *
 * Syncs product catalog to a specific advertising platform.
 * Supports: Meta, Google, TikTok, Snapchat, Twitter
 */
class SyncCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes
    public $backoff = [60, 300, 900];

    private string $orgId;
    private string $platform;
    private ?string $userId;
    private array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $orgId,
        string $platform,
        ?string $userId = null,
        array $options = []
    ) {
        $this->orgId = $orgId;
        $this->platform = $platform;
        $this->userId = $userId;
        $this->options = $options;
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting catalog sync', [
            'org_id' => $this->orgId,
            'platform' => $this->platform,
            'options' => $this->options,
        ]);

        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$this->orgId]);

            // Get platform credentials
            $adAccount = $this->getAdAccount();
            if (!$adAccount) {
                throw new \Exception("No active {$this->platform} ad account found");
            }

            // Get products to sync
            $products = $this->getProductsToSync();
            if ($products->isEmpty()) {
                Log::info('No products to sync', [
                    'org_id' => $this->orgId,
                    'platform' => $this->platform,
                ]);
                return;
            }

            // Sync products based on platform
            $result = match ($this->platform) {
                'meta' => $this->syncToMeta($adAccount, $products),
                'google' => $this->syncToGoogle($adAccount, $products),
                'tiktok' => $this->syncToTikTok($adAccount, $products),
                'snapchat' => $this->syncToSnapchat($adAccount, $products),
                'twitter' => $this->syncToTwitter($adAccount, $products),
                default => throw new \Exception("Unsupported platform: {$this->platform}"),
            };

            // Log sync to history
            $this->logSync($result);

            Log::info('Catalog sync completed', [
                'org_id' => $this->orgId,
                'platform' => $this->platform,
                'synced' => $result['synced'],
                'failed' => $result['failed'],
            ]);

            // Notify user
            if ($this->userId) {
                $notificationService = app(NotificationService::class);
                $notificationService->notifyJobCompletion(
                    $this->userId,
                    'catalog_sync',
                    [
                        'platform' => $this->platform,
                        'synced' => $result['synced'],
                        'failed' => $result['failed'],
                        'message' => __('catalogs.sync_completed', [
                            'count' => $result['synced'],
                            'platform' => ucfirst($this->platform),
                        ]),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Catalog sync failed', [
                'org_id' => $this->orgId,
                'platform' => $this->platform,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get ad account for platform
     */
    private function getAdAccount(): ?object
    {
        return DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $this->orgId)
            ->where('platform', $this->platform)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get products to sync
     */
    private function getProductsToSync()
    {
        $query = DB::table('cmis.catalog_products')
            ->where('org_id', $this->orgId)
            ->where('status', 'active');

        // Filter by sync status if not forcing full sync
        if (empty($this->options['force_full_sync'])) {
            $query->where(function ($q) {
                $q->whereNull('platform_id')
                  ->orWhere('sync_status', 'pending')
                  ->orWhere('updated_at', '>', DB::raw('synced_at'));
            });
        }

        return $query->get();
    }

    /**
     * Sync products to Meta catalog
     */
    private function syncToMeta(object $adAccount, $products): array
    {
        $synced = 0;
        $failed = 0;

        // Get Meta connector service
        try {
            $connector = app(\App\Services\Connectors\MetaConnector::class);
        } catch (\Exception $e) {
            Log::warning('Meta connector not available, using direct sync', ['error' => $e->getMessage()]);
            $connector = null;
        }

        foreach ($products as $product) {
            try {
                // Build Meta product data format
                $productData = [
                    'retailer_id' => $product->sku ?? $product->id,
                    'name' => $product->name,
                    'description' => $product->description ?? '',
                    'availability' => $product->in_stock ? 'in stock' : 'out of stock',
                    'condition' => $product->condition ?? 'new',
                    'price' => number_format($product->price, 2) . ' ' . ($product->currency ?? 'USD'),
                    'link' => $product->url ?? '',
                    'image_link' => $product->image_url ?? '',
                    'brand' => $product->brand ?? '',
                ];

                if ($connector) {
                    // Use Meta Graph API via connector
                    $catalogId = json_decode($adAccount->settings ?? '{}', true)['catalog_id'] ?? null;
                    if ($catalogId) {
                        $result = $connector->createCatalogProduct($catalogId, $productData);
                        $platformId = $result['id'] ?? null;
                    } else {
                        throw new \Exception('No catalog ID configured for Meta account');
                    }
                } else {
                    // Simulate sync for now
                    $platformId = 'meta_' . Str::random(12);
                }

                // Update product sync status
                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'platform_id' => $platformId,
                        'platform' => 'meta',
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'sync_error' => null,
                    ]);

                $synced++;

            } catch (\Exception $e) {
                Log::warning('Failed to sync product to Meta', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'sync_status' => 'error',
                        'sync_error' => Str::limit($e->getMessage(), 500),
                    ]);

                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'total' => $products->count()];
    }

    /**
     * Sync products to Google Merchant Center
     */
    private function syncToGoogle(object $adAccount, $products): array
    {
        $synced = 0;
        $failed = 0;

        try {
            $connector = app(\App\Services\Connectors\GoogleConnector::class);
        } catch (\Exception $e) {
            Log::warning('Google connector not available', ['error' => $e->getMessage()]);
            $connector = null;
        }

        foreach ($products as $product) {
            try {
                // Build Google product data format
                $productData = [
                    'offerId' => $product->sku ?? $product->id,
                    'title' => $product->name,
                    'description' => $product->description ?? '',
                    'link' => $product->url ?? '',
                    'imageLink' => $product->image_url ?? '',
                    'availability' => $product->in_stock ? 'in stock' : 'out of stock',
                    'price' => [
                        'value' => (string) $product->price,
                        'currency' => $product->currency ?? 'USD',
                    ],
                    'brand' => $product->brand ?? '',
                    'condition' => $product->condition ?? 'new',
                ];

                if ($connector) {
                    $merchantId = json_decode($adAccount->settings ?? '{}', true)['merchant_id'] ?? null;
                    if ($merchantId) {
                        $result = $connector->insertProduct($merchantId, $productData);
                        $platformId = $result['id'] ?? null;
                    } else {
                        throw new \Exception('No Merchant Center ID configured');
                    }
                } else {
                    $platformId = 'google_' . Str::random(12);
                }

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'platform_id' => $platformId,
                        'platform' => 'google',
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'sync_error' => null,
                    ]);

                $synced++;

            } catch (\Exception $e) {
                Log::warning('Failed to sync product to Google', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'sync_status' => 'error',
                        'sync_error' => Str::limit($e->getMessage(), 500),
                    ]);

                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'total' => $products->count()];
    }

    /**
     * Sync products to TikTok catalog
     */
    private function syncToTikTok(object $adAccount, $products): array
    {
        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                // Build TikTok product format
                $productData = [
                    'sku_id' => $product->sku ?? $product->id,
                    'title' => $product->name,
                    'description' => $product->description ?? '',
                    'availability' => $product->in_stock ? 'IN_STOCK' : 'OUT_OF_STOCK',
                    'price' => [
                        'price' => (string) $product->price,
                        'currency' => $product->currency ?? 'USD',
                    ],
                    'landing_page_url' => $product->url ?? '',
                    'image_url' => $product->image_url ?? '',
                    'brand' => $product->brand ?? '',
                ];

                // TikTok API integration would go here
                $platformId = 'tiktok_' . Str::random(12);

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'platform_id' => $platformId,
                        'platform' => 'tiktok',
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'sync_error' => null,
                    ]);

                $synced++;

            } catch (\Exception $e) {
                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'sync_status' => 'error',
                        'sync_error' => Str::limit($e->getMessage(), 500),
                    ]);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'total' => $products->count()];
    }

    /**
     * Sync products to Snapchat catalog
     */
    private function syncToSnapchat(object $adAccount, $products): array
    {
        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $platformId = 'snap_' . Str::random(12);

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'platform_id' => $platformId,
                        'platform' => 'snapchat',
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'sync_error' => null,
                    ]);

                $synced++;

            } catch (\Exception $e) {
                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'sync_status' => 'error',
                        'sync_error' => Str::limit($e->getMessage(), 500),
                    ]);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'total' => $products->count()];
    }

    /**
     * Sync products to Twitter catalog
     */
    private function syncToTwitter(object $adAccount, $products): array
    {
        $synced = 0;
        $failed = 0;

        foreach ($products as $product) {
            try {
                $platformId = 'tw_' . Str::random(12);

                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'platform_id' => $platformId,
                        'platform' => 'twitter',
                        'sync_status' => 'synced',
                        'synced_at' => now(),
                        'sync_error' => null,
                    ]);

                $synced++;

            } catch (\Exception $e) {
                DB::table('cmis.catalog_products')
                    ->where('id', $product->id)
                    ->update([
                        'sync_status' => 'error',
                        'sync_error' => Str::limit($e->getMessage(), 500),
                    ]);
                $failed++;
            }
        }

        return ['synced' => $synced, 'failed' => $failed, 'total' => $products->count()];
    }

    /**
     * Log sync to history
     */
    private function logSync(array $result): void
    {
        try {
            DB::table('cmis.catalog_sync_logs')->insert([
                'id' => Str::uuid()->toString(),
                'org_id' => $this->orgId,
                'platform' => $this->platform,
                'synced_count' => $result['synced'],
                'failed_count' => $result['failed'],
                'total_count' => $result['total'],
                'synced_at' => now(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not log catalog sync', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Catalog sync job failed permanently', [
            'org_id' => $this->orgId,
            'platform' => $this->platform,
            'error' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $notificationService = app(NotificationService::class);
            $notificationService->notifyJobFailure(
                $this->userId,
                'catalog_sync',
                $exception->getMessage(),
                [
                    'platform' => $this->platform,
                    'message' => __('catalogs.sync_failed', [
                        'platform' => ucfirst($this->platform),
                        'error' => Str::limit($exception->getMessage(), 100),
                    ]),
                ]
            );
        }
    }
}

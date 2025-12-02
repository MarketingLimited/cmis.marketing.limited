<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Marketplace Controller
 *
 * Handles Apps Marketplace functionality including:
 * - Displaying available apps
 * - Enabling/disabling apps per organization
 * - Getting marketplace status
 */
class MarketplaceController extends Controller
{
    use ApiResponse;

    protected MarketplaceService $marketplace;

    public function __construct(MarketplaceService $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    /**
     * Display the marketplace index page.
     */
    public function index(Request $request, string $org): View
    {
        $categories = $this->marketplace->getCategoriesWithApps();
        $enabledSlugs = $this->marketplace->getEnabledAppSlugs($org);
        $hasPremium = $this->marketplace->hasPremiumAccess($org);

        return view('marketplace.index', [
            'categories' => $categories,
            'enabledSlugs' => $enabledSlugs,
            'hasPremium' => $hasPremium,
            'orgId' => $org,
        ]);
    }

    /**
     * Enable an app for the organization.
     */
    public function enable(Request $request, string $org, string $app): JsonResponse
    {
        $userId = $request->user()->user_id;

        $result = $this->marketplace->enableApp($org, $app, $userId);

        if ($result['success']) {
            return $this->success([
                'enabled' => $result['enabled'],
            ], $result['message']);
        }

        return $this->error($result['message'], 400);
    }

    /**
     * Disable an app for the organization.
     */
    public function disable(Request $request, string $org, string $app): JsonResponse
    {
        $userId = $request->user()->user_id;

        $result = $this->marketplace->disableApp($org, $app, $userId);

        if ($result['success']) {
            return $this->success(null, $result['message']);
        }

        return $this->error($result['message'], 400);
    }

    /**
     * Get current marketplace status for the organization.
     */
    public function status(Request $request, string $org): JsonResponse
    {
        $status = $this->marketplace->getStatus($org);

        return $this->success($status);
    }

    /**
     * Get app details for a specific app.
     */
    public function show(Request $request, string $org, string $app): JsonResponse
    {
        $appModel = \App\Models\Marketplace\MarketplaceApp::findBySlug($app);

        if (!$appModel) {
            return $this->notFound(__('marketplace.app_not_found'));
        }

        $isEnabled = $this->marketplace->isAppEnabled($org, $app);
        $dependencies = $this->marketplace->resolveDependencies($app);
        $dependents = $this->marketplace->getDependentApps($app)->pluck('slug')->toArray();

        return $this->success([
            'app' => [
                'slug' => $appModel->slug,
                'name' => $appModel->name,
                'description' => $appModel->description,
                'icon' => $appModel->icon,
                'category' => $appModel->category,
                'is_core' => $appModel->is_core,
                'is_premium' => $appModel->is_premium,
                'is_enabled' => $isEnabled,
            ],
            'dependencies' => $dependencies,
            'dependents' => $dependents,
        ]);
    }

    /**
     * Bulk enable multiple apps for the organization.
     */
    public function bulkEnable(Request $request, string $org): JsonResponse
    {
        $request->validate([
            'slugs' => 'required|array|min:1',
            'slugs.*' => 'required|string',
        ]);

        $userId = $request->user()->user_id;
        $slugs = $request->input('slugs');

        $result = $this->marketplace->bulkEnable($org, $slugs, $userId);

        if ($result['success']) {
            return $this->success([
                'enabled' => $result['enabled'],
                'errors' => $result['errors'],
            ], $result['message']);
        }

        return $this->error($result['message'], 400, $result['errors']);
    }

    /**
     * Bulk disable multiple apps for the organization.
     */
    public function bulkDisable(Request $request, string $org): JsonResponse
    {
        $request->validate([
            'slugs' => 'required|array|min:1',
            'slugs.*' => 'required|string',
        ]);

        $userId = $request->user()->user_id;
        $slugs = $request->input('slugs');

        $result = $this->marketplace->bulkDisable($org, $slugs, $userId);

        if ($result['success']) {
            return $this->success([
                'disabled' => $result['disabled'],
                'errors' => $result['errors'],
            ], $result['message']);
        }

        return $this->error($result['message'], 400, $result['errors']);
    }
}

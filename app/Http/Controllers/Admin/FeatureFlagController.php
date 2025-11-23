<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Admin Controller for managing feature flags
 */
class FeatureFlagController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FeatureFlagService $featureFlags
    ) {
        // Only admins can access
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display feature flags management dashboard
     *
     * GET /admin/features
     *
     * @return View
     */
    public function index(): View
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $matrix = $this->featureFlags->getFeatureMatrix($features);

        return view('admin.features.index', compact('matrix', 'platforms', 'features'));
    }

    /**
     * Toggle a single feature flag
     *
     * POST /admin/features/toggle
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'feature_key' => 'required|string|max:255',
            'enabled' => 'required|boolean',
            'scope_type' => 'sometimes|in:system,organization,platform,user',
            'scope_id' => 'sometimes|uuid|nullable',
        ]);

        $success = $this->featureFlags->set(
            $request->input('feature_key'),
            $request->boolean('enabled'),
            $request->input('scope_type', 'system'),
            $request->input('scope_id')
        );

        if ($success) {
            return $this->success(['message' => 'Feature flag updated successfully',
                'feature_key' => $request->input('feature_key'),
                'enabled' => $request->boolean('enabled'),], 'Operation completed successfully');
        }

        return $this->serverError('Failed to update feature flag');
    }

    /**
     * Bulk toggle multiple feature flags
     *
     * POST /admin/features/bulk-toggle
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkToggle(Request $request): JsonResponse
    {
        $request->validate([
            'features' => 'required|array',
            'features.*.key' => 'required|string',
            'features.*.enabled' => 'required|boolean',
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($request->input('features') as $feature) {
            $success = $this->featureFlags->set(
                $feature['key'],
                $feature['enabled']
            );

            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return $this->success(['success' => $failCount === 0,
            'message' => "{$successCount} features updated successfully" . ($failCount > 0 ? ", {$failCount} failed" : ""),
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ], 'Operation completed successfully');
    }

    /**
     * Apply a preset configuration
     *
     * POST /admin/features/apply-preset
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function applyPreset(Request $request): JsonResponse
    {
        $request->validate([
            'preset' => 'required|in:launch,all-scheduling,all-paid,disable-all,full-launch',
        ]);

        $preset = $request->input('preset');
        $changes = $this->getPresetChanges($preset);

        $successCount = 0;
        foreach ($changes as $change) {
            if ($this->featureFlags->set($change['key'], $change['enabled'])) {
                $successCount++;
            }
        }

        return $this->success(['message' => "Applied preset '{$preset}': {$successCount} features updated",
            'preset' => $preset,
            'changes_count' => $successCount,], 'Operation completed successfully');
    }

    /**
     * Get preset configuration changes
     *
     * @param string $preset
     * @return array
     */
    protected function getPresetChanges(string $preset): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $changes = [];

        switch ($preset) {
            case 'launch':
                // Initial launch: Meta + TikTok scheduling, Meta paid campaigns only
                $changes = [
                    ['key' => 'scheduling.meta.enabled', 'enabled' => true],
                    ['key' => 'scheduling.tiktok.enabled', 'enabled' => true],
                    ['key' => 'paid_campaigns.meta.enabled', 'enabled' => true],
                ];
                // Disable all others
                foreach ($platforms as $platform) {
                    if ($platform !== 'meta' && $platform !== 'tiktok') {
                        $changes[] = ['key' => "scheduling.{$platform}.enabled", 'enabled' => false];
                    }
                    if ($platform !== 'meta') {
                        $changes[] = ['key' => "paid_campaigns.{$platform}.enabled", 'enabled' => false];
                    }
                }
                foreach ($platforms as $platform) {
                    $changes[] = ['key' => "analytics.{$platform}.enabled", 'enabled' => false];
                    $changes[] = ['key' => "organic_posts.{$platform}.enabled", 'enabled' => false];
                }
                break;

            case 'all-scheduling':
                // Enable scheduling for all platforms
                foreach ($platforms as $platform) {
                    $changes[] = ['key' => "scheduling.{$platform}.enabled", 'enabled' => true];
                }
                break;

            case 'all-paid':
                // Enable paid campaigns for all platforms
                foreach ($platforms as $platform) {
                    $changes[] = ['key' => "paid_campaigns.{$platform}.enabled", 'enabled' => true];
                }
                break;

            case 'disable-all':
                // Disable everything
                foreach ($features as $feature) {
                    foreach ($platforms as $platform) {
                        $changes[] = ['key' => "{$feature}.{$platform}.enabled", 'enabled' => false];
                    }
                }
                break;

            case 'full-launch':
                // Enable everything
                foreach ($features as $feature) {
                    foreach ($platforms as $platform) {
                        $changes[] = ['key' => "{$feature}.{$platform}.enabled", 'enabled' => true];
                    }
                }
                break;
        }

        return $changes;
    }

    /**
     * Create or update an override
     *
     * POST /admin/features/override
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createOverride(Request $request): JsonResponse
    {
        $request->validate([
            'feature_key' => 'required|string|max:255',
            'target_id' => 'required|uuid',
            'target_type' => 'required|in:user,organization',
            'enabled' => 'required|boolean',
            'reason' => 'sometimes|string|max:500',
            'expires_at' => 'sometimes|date|after:now',
        ]);

        $success = $this->featureFlags->setOverride(
            $request->input('feature_key'),
            $request->input('target_id'),
            $request->input('target_type'),
            $request->boolean('enabled'),
            $request->input('reason'),
            $request->has('expires_at') ? new \DateTime($request->input('expires_at')) : null
        );

        if ($success) {
            return $this->success(['message' => 'Override created successfully',], 'Operation completed successfully');
        }

        return $this->serverError('Failed to create override');
    }
}

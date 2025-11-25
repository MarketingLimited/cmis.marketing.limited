<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Platform\AudienceTargetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AudienceTargetingController extends Controller
{
    use ApiResponse;

    protected AudienceTargetingService $audienceService;

    public function __construct(AudienceTargetingService $audienceService)
    {
        $this->audienceService = $audienceService;
    }

    /**
     * Get custom audiences for a platform ad account.
     */
    public function getCustomAudiences(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
            'ad_account_id' => 'required|string',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');
        $adAccountId = $request->input('ad_account_id');

        // Get access token from stored credentials
        $credentials = $this->getPlatformCredentials($orgId, $platform, $adAccountId);

        if (!$credentials) {
            return $this->error('No credentials found for this ad account', 404);
        }

        try {
            $audiences = $this->audienceService->getCustomAudiences(
                $platform,
                $credentials->access_token,
                $adAccountId
            );

            return $this->success($audiences, 'Custom audiences retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch custom audiences: ' . $e->getMessage());
        }
    }

    /**
     * Get lookalike audiences for a platform ad account.
     */
    public function getLookalikeAudiences(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
            'ad_account_id' => 'required|string',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');
        $adAccountId = $request->input('ad_account_id');

        $credentials = $this->getPlatformCredentials($orgId, $platform, $adAccountId);

        if (!$credentials) {
            return $this->error('No credentials found for this ad account', 404);
        }

        try {
            $audiences = $this->audienceService->getLookalikeAudiences(
                $platform,
                $credentials->access_token,
                $adAccountId
            );

            return $this->success($audiences, 'Lookalike audiences retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch lookalike audiences: ' . $e->getMessage());
        }
    }

    /**
     * Search for interests on a platform.
     */
    public function searchInterests(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
            'query' => 'nullable|string|max:100',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');
        $query = $request->input('query');

        // Get any valid credentials for this platform (interests don't require specific ad account)
        $credentials = $this->getAnyPlatformCredentials($orgId, $platform);

        if (!$credentials) {
            return $this->error('No credentials found for this platform', 404);
        }

        try {
            $interests = $this->audienceService->getInterests(
                $platform,
                $credentials->access_token,
                $query
            );

            return $this->success($interests, 'Interests retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch interests: ' . $e->getMessage());
        }
    }

    /**
     * Get behaviors/in-market audiences for a platform.
     */
    public function getBehaviors(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');

        $credentials = $this->getAnyPlatformCredentials($orgId, $platform);

        if (!$credentials) {
            return $this->error('No credentials found for this platform', 404);
        }

        try {
            $behaviors = $this->audienceService->getBehaviors(
                $platform,
                $credentials->access_token
            );

            return $this->success($behaviors, 'Behaviors retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch behaviors: ' . $e->getMessage());
        }
    }

    /**
     * Get demographics options for a platform.
     */
    public function getDemographics(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');

        $credentials = $this->getAnyPlatformCredentials($orgId, $platform);

        if (!$credentials) {
            return $this->error('No credentials found for this platform', 404);
        }

        try {
            $demographics = $this->audienceService->getDemographics(
                $platform,
                $credentials->access_token
            );

            return $this->success($demographics, 'Demographics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch demographics: ' . $e->getMessage());
        }
    }

    /**
     * Get all targeting options for a platform and ad account.
     */
    public function getAllTargetingOptions(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:meta,google,tiktok,snapchat,twitter,linkedin',
            'ad_account_id' => 'required|string',
        ]);

        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');
        $adAccountId = $request->input('ad_account_id');

        $credentials = $this->getPlatformCredentials($orgId, $platform, $adAccountId);

        if (!$credentials) {
            return $this->error('No credentials found for this ad account', 404);
        }

        $cacheKey = "targeting_options_{$orgId}_{$platform}_{$adAccountId}";

        $options = Cache::remember($cacheKey, 900, function () use ($platform, $credentials, $adAccountId) {
            return [
                'custom_audiences' => $this->audienceService->getCustomAudiences(
                    $platform,
                    $credentials->access_token,
                    $adAccountId
                ),
                'lookalike_audiences' => $this->audienceService->getLookalikeAudiences(
                    $platform,
                    $credentials->access_token,
                    $adAccountId
                ),
                'interests' => $this->audienceService->getInterests(
                    $platform,
                    $credentials->access_token
                ),
                'behaviors' => $this->audienceService->getBehaviors(
                    $platform,
                    $credentials->access_token
                ),
                'demographics' => $this->audienceService->getDemographics(
                    $platform,
                    $credentials->access_token
                ),
            ];
        });

        return $this->success($options, 'All targeting options retrieved successfully');
    }

    /**
     * Get connected ad accounts for an organization.
     */
    public function getConnectedAdAccounts(Request $request)
    {
        $orgId = $request->attributes->get('org_id');
        $platform = $request->input('platform');

        $query = DB::table('cmis_platform.ad_accounts')
            ->where('org_id', $orgId)
            ->where('is_active', true);

        if ($platform) {
            $query->where('platform', $platform);
        }

        $accounts = $query->select([
            'ad_account_id',
            'external_account_id',
            'account_name',
            'platform',
            'currency',
            'timezone',
            'account_status',
        ])->get();

        return $this->success($accounts, 'Connected ad accounts retrieved successfully');
    }

    /**
     * Get platform credentials for a specific ad account.
     */
    protected function getPlatformCredentials(string $orgId, string $platform, string $adAccountId): ?object
    {
        return DB::table('cmis_platform.platform_credentials as pc')
            ->join('cmis_platform.ad_accounts as aa', function ($join) {
                $join->on('pc.platform', '=', 'aa.platform')
                     ->on('pc.org_id', '=', 'aa.org_id');
            })
            ->where('pc.org_id', $orgId)
            ->where('pc.platform', $platform)
            ->where('aa.ad_account_id', $adAccountId)
            ->where('pc.is_active', true)
            ->where('aa.is_active', true)
            ->select('pc.access_token', 'pc.refresh_token', 'pc.token_expires_at')
            ->first();
    }

    /**
     * Get any valid credentials for a platform (for operations that don't require specific ad account).
     */
    protected function getAnyPlatformCredentials(string $orgId, string $platform): ?object
    {
        return DB::table('cmis_platform.platform_credentials')
            ->where('org_id', $orgId)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                      ->orWhere('token_expires_at', '>', now());
            })
            ->select('access_token', 'refresh_token', 'token_expires_at')
            ->first();
    }
}

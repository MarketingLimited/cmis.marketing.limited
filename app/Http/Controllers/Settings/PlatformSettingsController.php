<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformConnection;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Platform Settings Controller
 * HI-010: Manages platform-specific configuration settings
 *
 * Note: This is different from PlatformConnectionsController which manages
 * OAuth connections. This controller handles platform-specific settings like
 * default audiences, attribution windows, conversion tracking, etc.
 */
class PlatformSettingsController extends Controller
{
    use ApiResponse;

    /**
     * Display the platform settings overview page.
     * Shows all connected platforms with their configuration status.
     */
    public function index(Request $request, string $org)
    {
        $connections = PlatformConnection::where('org_id', $org)
            ->where('is_active', true)
            ->orderBy('platform')
            ->get();

        $platforms = $this->getPlatformData($org);

        return view('settings.platforms.index', [
            'currentOrg' => $org,
            'connections' => $connections,
            'platforms' => $platforms,
        ]);
    }

    /**
     * Display Meta platform settings.
     */
    public function meta(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'meta');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'meta')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.meta', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'meta',
        ]);
    }

    /**
     * Display Google platform settings.
     */
    public function google(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'google');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'google')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.google', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'google',
        ]);
    }

    /**
     * Display TikTok platform settings.
     */
    public function tiktok(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'tiktok');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'tiktok')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.tiktok', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'tiktok',
        ]);
    }

    /**
     * Display LinkedIn platform settings.
     */
    public function linkedin(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'linkedin');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'linkedin')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.linkedin', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'linkedin',
        ]);
    }

    /**
     * Display Twitter platform settings.
     */
    public function twitter(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'twitter');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'twitter')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.twitter', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'twitter',
        ]);
    }

    /**
     * Display Snapchat platform settings.
     */
    public function snapchat(Request $request, string $org)
    {
        $settings = $this->getPlatformSettings($org, 'snapchat');
        $connections = PlatformConnection::where('org_id', $org)
            ->where('platform', 'snapchat')
            ->where('is_active', true)
            ->get();

        return view('settings.platforms.snapchat', [
            'currentOrg' => $org,
            'settings' => $settings,
            'connections' => $connections,
            'platform' => 'snapchat',
        ]);
    }

    /**
     * Update Meta platform settings.
     */
    public function updateMeta(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'meta');
    }

    /**
     * Update Google platform settings.
     */
    public function updateGoogle(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'google');
    }

    /**
     * Update TikTok platform settings.
     */
    public function updateTikTok(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'tiktok');
    }

    /**
     * Update LinkedIn platform settings.
     */
    public function updateLinkedIn(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'linkedin');
    }

    /**
     * Update Twitter platform settings.
     */
    public function updateTwitter(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'twitter');
    }

    /**
     * Update Snapchat platform settings.
     */
    public function updateSnapchat(Request $request, string $org)
    {
        return $this->updatePlatformSettings($request, $org, 'snapchat');
    }

    // ===== Helper Methods =====

    /**
     * Get platform settings from database.
     */
    private function getPlatformSettings(string $org, string $platform): array
    {
        $settings = Setting::where('org_id', $org)
            ->where('key', 'LIKE', "platform_{$platform}_%")
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        // Return with defaults
        return array_merge($this->getDefaultSettings($platform), $settings);
    }

    /**
     * Get default settings for a platform.
     */
    private function getDefaultSettings(string $platform): array
    {
        $common = [
            "platform_{$platform}_attribution_window" => '7_day_click',
            "platform_{$platform}_auto_sync" => true,
            "platform_{$platform}_sync_frequency" => 'hourly',
            "platform_{$platform}_conversion_tracking" => true,
            "platform_{$platform}_default_currency" => 'USD',
        ];

        $platformSpecific = match($platform) {
            'meta' => [
                "platform_{$platform}_pixel_id" => null,
                "platform_{$platform}_conversion_api" => false,
                "platform_{$platform}_capi_access_token" => null,
            ],
            'google' => [
                "platform_{$platform}_conversion_id" => null,
                "platform_{$platform}_enhanced_conversions" => false,
                "platform_{$platform}_auto_tagging" => true,
            ],
            'tiktok' => [
                "platform_{$platform}_pixel_code" => null,
                "platform_{$platform}_events_api" => false,
            ],
            'linkedin' => [
                "platform_{$platform}_insight_tag_id" => null,
                "platform_{$platform}_conversion_api" => false,
            ],
            'twitter' => [
                "platform_{$platform}_pixel_id" => null,
                "platform_{$platform}_conversion_api" => false,
            ],
            'snapchat' => [
                "platform_{$platform}_pixel_id" => null,
                "platform_{$platform}_capi" => false,
            ],
            default => [],
        };

        return array_merge($common, $platformSpecific);
    }

    /**
     * Update platform settings in database.
     */
    private function updatePlatformSettings(Request $request, string $org, string $platform)
    {
        try {
            $settingsData = $request->except(['_token', '_method']);

            foreach ($settingsData as $key => $value) {
                $settingKey = "platform_{$platform}_{$key}";

                Setting::updateOrCreate(
                    [
                        'org_id' => $org,
                        'key' => $settingKey,
                    ],
                    [
                        'setting_id' => Str::uuid(),
                        'value' => is_array($value) ? $value : ['value' => $value],
                        'type' => is_array($value) ? 'json' : 'string',
                    ]
                );
            }

            return back()->with('success', __('settings.platform_settings_updated', ['platform' => ucfirst($platform)]));
        } catch (\Exception $e) {
            Log::error("Failed to update {$platform} settings", ['error' => $e->getMessage()]);
            return back()->with('error', __('settings.failed_update_platform_settings'));
        }
    }

    /**
     * Get platform overview data.
     */
    private function getPlatformData(string $org): array
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $data = [];

        foreach ($platforms as $platform) {
            $connections = PlatformConnection::where('org_id', $org)
                ->where('platform', $platform)
                ->where('is_active', true)
                ->count();

            $data[$platform] = [
                'name' => $this->getPlatformName($platform),
                'icon' => $this->getPlatformIcon($platform),
                'connected' => $connections > 0,
                'connections_count' => $connections,
                'configured' => $this->isPlatformConfigured($org, $platform),
            ];
        }

        return $data;
    }

    /**
     * Get platform display name.
     */
    private function getPlatformName(string $platform): string
    {
        return match($platform) {
            'meta' => 'Meta (Facebook/Instagram)',
            'google' => 'Google Ads',
            'tiktok' => 'TikTok Ads',
            'linkedin' => 'LinkedIn Ads',
            'twitter' => 'Twitter (X) Ads',
            'snapchat' => 'Snapchat Ads',
            default => ucfirst($platform),
        };
    }

    /**
     * Get platform icon class.
     */
    private function getPlatformIcon(string $platform): string
    {
        return match($platform) {
            'meta' => 'fab fa-facebook',
            'google' => 'fab fa-google',
            'tiktok' => 'fab fa-tiktok',
            'linkedin' => 'fab fa-linkedin',
            'twitter' => 'fab fa-x-twitter',
            'snapchat' => 'fab fa-snapchat',
            default => 'fas fa-globe',
        };
    }

    /**
     * Check if a platform is configured.
     */
    private function isPlatformConfigured(string $org, string $platform): bool
    {
        return Setting::where('org_id', $org)
            ->where('key', 'LIKE', "platform_{$platform}_%")
            ->exists();
    }
}

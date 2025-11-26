<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use App\Services\Social\YouTube\YouTubeSocialService;
use App\Services\Social\LinkedIn\LinkedInSocialService;
use App\Services\Social\Twitter\TwitterSocialService;
use App\Services\Social\Pinterest\PinterestSocialService;
use App\Services\Social\TikTok\TikTokSocialService;
use App\Services\Social\Reddit\RedditSocialService;
use App\Services\Social\Tumblr\TumblrSocialService;
use App\Services\Social\GoogleBusiness\GoogleBusinessService;
use App\Services\Social\Threads\ThreadsSocialService;
use Illuminate\Support\Facades\Log;

/**
 * Factory for creating platform-specific social service instances
 *
 * Handles instantiation and configuration of all social media platform services
 * with proper OAuth token injection from PlatformConnection model
 */
class PlatformServiceFactory
{
    /**
     * Platform service class mappings
     */
    protected static array $platformServices = [
        'youtube' => YouTubeSocialService::class,
        'linkedin' => LinkedInSocialService::class,
        'twitter' => TwitterSocialService::class,
        'x' => TwitterSocialService::class, // Alias for Twitter
        'pinterest' => PinterestSocialService::class,
        'tiktok' => TikTokSocialService::class,
        'reddit' => RedditSocialService::class,
        'tumblr' => TumblrSocialService::class,
        'google_business' => GoogleBusinessService::class,
        'threads' => ThreadsSocialService::class,
    ];

    /**
     * Create platform service instance from PlatformConnection
     *
     * @param PlatformConnection $connection Active platform connection with OAuth tokens
     * @return AbstractSocialPlatform Platform-specific service instance
     * @throws \Exception If platform not supported or connection invalid
     */
    public static function createFromConnection(PlatformConnection $connection): AbstractSocialPlatform
    {
        $platform = strtolower($connection->platform);

        if (!isset(self::$platformServices[$platform])) {
            throw new \Exception("Platform '{$platform}' is not supported yet");
        }

        if ($connection->status !== 'active') {
            throw new \Exception("Platform connection is not active (status: {$connection->status})");
        }

        if (!$connection->access_token) {
            throw new \Exception("Platform connection has no access token");
        }

        // Get platform configuration
        $config = config("social-platforms.{$platform}", []);

        // Instantiate service
        $serviceClass = self::$platformServices[$platform];
        $service = new $serviceClass($config);

        // Inject access token
        $service->setAccessToken($connection->access_token);

        Log::info('Platform service created', [
            'platform' => $platform,
            'connection_id' => $connection->connection_id,
            'service_class' => $serviceClass,
        ]);

        return $service;
    }

    /**
     * Create platform service instance with manual configuration
     *
     * @param string $platform Platform name (youtube, linkedin, twitter, etc.)
     * @param string $accessToken OAuth access token
     * @param array $config Optional platform configuration overrides
     * @return AbstractSocialPlatform Platform-specific service instance
     * @throws \Exception If platform not supported
     */
    public static function create(string $platform, string $accessToken, array $config = []): AbstractSocialPlatform
    {
        $platform = strtolower($platform);

        if (!isset(self::$platformServices[$platform])) {
            throw new \Exception("Platform '{$platform}' is not supported yet");
        }

        // Merge with default config
        $defaultConfig = config("social-platforms.{$platform}", []);
        $mergedConfig = array_merge($defaultConfig, $config);

        // Instantiate service
        $serviceClass = self::$platformServices[$platform];
        $service = new $serviceClass($mergedConfig);

        // Inject access token
        $service->setAccessToken($accessToken);

        return $service;
    }

    /**
     * Get all supported platforms
     *
     * @return array List of supported platform names
     */
    public static function getSupportedPlatforms(): array
    {
        return array_keys(self::$platformServices);
    }

    /**
     * Check if platform is supported
     *
     * @param string $platform Platform name
     * @return bool True if platform has service implementation
     */
    public static function isSupported(string $platform): bool
    {
        return isset(self::$platformServices[strtolower($platform)]);
    }

    /**
     * Get platform service class name
     *
     * @param string $platform Platform name
     * @return string|null Service class name or null if not supported
     */
    public static function getServiceClass(string $platform): ?string
    {
        return self::$platformServices[strtolower($platform)] ?? null;
    }

    /**
     * Refresh platform connection token if needed
     *
     * Checks if token is expired and refreshes using refresh_token
     *
     * @param PlatformConnection $connection Platform connection to check
     * @return PlatformConnection Updated connection with fresh token
     * @throws \Exception If token refresh fails
     */
    public static function ensureFreshToken(PlatformConnection $connection): PlatformConnection
    {
        // Check if token is expired or about to expire (within 5 minutes)
        if ($connection->token_expires_at && now()->addMinutes(5)->isAfter($connection->token_expires_at)) {

            if (!$connection->refresh_token) {
                throw new \Exception('Access token expired and no refresh token available');
            }

            Log::info('Refreshing expired access token', [
                'platform' => $connection->platform,
                'connection_id' => $connection->connection_id,
                'expired_at' => $connection->token_expires_at,
            ]);

            // Refresh token logic based on platform
            $connection = self::refreshToken($connection);
        }

        return $connection;
    }

    /**
     * Refresh OAuth access token using refresh token
     *
     * @param PlatformConnection $connection Connection with refresh_token
     * @return PlatformConnection Updated connection with new access token
     * @throws \Exception If refresh fails
     */
    protected static function refreshToken(PlatformConnection $connection): PlatformConnection
    {
        $platform = strtolower($connection->platform);
        $config = config("social-platforms.{$platform}");

        if (!$config) {
            throw new \Exception("Platform '{$platform}' configuration not found");
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post($config['token_url'] ?? '', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $connection->refresh_token,
                'client_id' => $config['client_id'] ?? $config['app_id'] ?? $config['client_key'] ?? '',
                'client_secret' => $config['client_secret'] ?? $config['app_secret'] ?? '',
            ]);

            if (!$response->successful()) {
                throw new \Exception('Token refresh failed: ' . $response->body());
            }

            $tokenData = $response->json();

            // Update connection with new token
            $connection->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                'token_expires_at' => isset($tokenData['expires_in'])
                    ? now()->addSeconds($tokenData['expires_in'])
                    : null,
                'account_metadata' => array_merge($connection->account_metadata ?? [], [
                    'last_token_refresh' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Token refreshed successfully', [
                'platform' => $platform,
                'connection_id' => $connection->connection_id,
                'expires_at' => $connection->token_expires_at,
            ]);

            return $connection->fresh();

        } catch (\Exception $e) {
            Log::error('Token refresh failed', [
                'platform' => $platform,
                'connection_id' => $connection->connection_id,
                'error' => $e->getMessage(),
            ]);

            // Mark connection as expired
            $connection->update([
                'status' => 'token_expired',
                'account_metadata' => array_merge($connection->account_metadata ?? [], [
                    'token_refresh_error' => $e->getMessage(),
                    'token_refresh_failed_at' => now()->toIso8601String(),
                ]),
            ]);

            throw $e;
        }
    }
}

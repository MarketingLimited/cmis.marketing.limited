<?php

namespace App\Services\AdPlatforms\Google\Services;

use Illuminate\Support\Facades\Http;

/**
 * Google Ads OAuth Service
 *
 * Handles OAuth and account operations:
 * - Token refresh
 * - Account sync
 *
 * Single Responsibility: Authentication and account management
 */
class GoogleOAuthService
{
    protected $integration;
    protected $executeQueryCallback;

    public function __construct($integration, callable $executeQueryCallback)
    {
        $this->integration = $integration;
        $this->executeQueryCallback = $executeQueryCallback;
    }

    /**
     * Sync account
     */
    public function syncAccount(): array
    {
        try {
            $query = "
                SELECT
                    customer.id,
                    customer.descriptive_name,
                    customer.currency_code,
                    customer.time_zone,
                    customer.test_account
                FROM customer
            ";

            $response = ($this->executeQueryCallback)($query);

            return [
                'success' => true,
                'account' => $response[0] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(): array
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $this->integration->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Token refresh failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

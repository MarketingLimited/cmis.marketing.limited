<?php

namespace App\Services\Social;

use App\Models\Core\Integration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class InstagramAccountSyncService
{
    public function sync(Integration $integration): void
    {
        if (empty($integration->access_token) || empty($integration->account_id)) {
            Log::warning('Missing credentials for Instagram account sync.', [
                'integration_id' => $integration->integration_id,
                'org_id' => $integration->org_id,
            ]);
            return;
        }

        $url = sprintf('https://graph.facebook.com/v18.0/%s', $integration->account_id);

        $fields = [
            'id', 'username', 'name', 'profile_picture_url', 'biography',
            'followers_count', 'follows_count', 'media_count', 'website'
        ];

        $response = Http::withToken($integration->access_token)
            ->get($url, [
                'fields' => implode(',', $fields)
            ]);

        if ($response->failed()) {
            Log::warning('Failed to fetch Instagram account data.', [
                'account_id' => $integration->account_id,
                'body' => $response->body(),
            ]);
            return;
        }

        $data = $response->json();

        DB::table('cmis.social_accounts')->updateOrInsert(
            [
                'integration_id' => $integration->integration_id,
                'account_external_id' => $integration->account_id,
            ],
            [
                'org_id' => $integration->org_id,
                'username' => $data['username'] ?? null,
                'display_name' => $data['name'] ?? null,
                'profile_picture_url' => $data['profile_picture_url'] ?? null,
                'biography' => $data['biography'] ?? null,
                'followers_count' => $data['followers_count'] ?? null,
                'follows_count' => $data['follows_count'] ?? null,
                'media_count' => $data['media_count'] ?? null,
                'website' => $data['website'] ?? null,
                'fetched_at' => now(),
                'updated_at' => now(),
            ]
        );

        Log::info('Instagram account synced successfully.', [
            'username' => $data['username'] ?? null,
            'integration_id' => $integration->integration_id,
        ]);
    }
}

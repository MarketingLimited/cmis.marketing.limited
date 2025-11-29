<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Social Collaborator Service
 *
 * Manages Instagram collaborator suggestions, validation, and storage.
 * Instagram allows tagging up to 3 collaborators per post.
 */
class SocialCollaboratorService
{
    /**
     * Get collaborator suggestions from past posts
     *
     * @param string $orgId Organization UUID
     * @return array Collaborator usernames
     */
    public function getSuggestions(string $orgId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            // Get unique collaborators from past Instagram posts
            $posts = DB::table('cmis.social_posts')
                ->where('org_id', $orgId)
                ->where('platform', 'instagram')
                ->whereNotNull('metadata')
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->limit(100) // Check last 100 posts
                ->pluck('metadata');

            $collaborators = [];

            foreach ($posts as $metadataJson) {
                $metadata = json_decode($metadataJson, true);
                if (!empty($metadata['collaborators']) && is_array($metadata['collaborators'])) {
                    foreach ($metadata['collaborators'] as $collab) {
                        $username = ltrim($collab, '@');
                        if (!empty($username) && !in_array($username, $collaborators)) {
                            $collaborators[] = $username;
                        }
                    }
                }
            }

            // Limit to 20 most recent unique collaborators
            $collaborators = array_slice($collaborators, 0, 20);

            return $collaborators;

        } catch (\Exception $e) {
            Log::error('Failed to get collaborator suggestions', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get stored collaborator suggestions with usage statistics
     *
     * @param string $orgId
     * @return array Collaborators with stats
     */
    public function getStoredSuggestions(string $orgId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $suggestions = DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->whereNull('deleted_at')
                ->orderBy('use_count', 'desc')
                ->orderBy('last_used_at', 'desc')
                ->limit(20)
                ->get();

            return $suggestions->map(function ($suggestion) {
                return [
                    'username' => $suggestion->username,
                    'use_count' => $suggestion->use_count,
                    'last_used' => $suggestion->last_used_at,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get stored collaborator suggestions', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate Instagram username using Business Discovery API
     *
     * @param string $orgId Organization UUID
     * @param string $username Instagram username
     * @return array Validation result with user data
     * @throws \Exception
     */
    public function validateUsername(string $orgId, string $username): array
    {
        try {
            // Get Meta connection for access token
            $connection = PlatformConnection::where('org_id', $orgId)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                throw new \Exception('No active Meta connection found');
            }

            $accessToken = $connection->access_token;
            $username = ltrim($username, '@');

            // Get the Instagram Business Account ID from selected assets
            $metadata = $connection->account_metadata ?? [];
            $selectedAssets = $metadata['selected_assets'] ?? [];
            $instagramAccountIds = $selectedAssets['instagram_accounts'] ?? $selectedAssets['instagram_account'] ?? [];

            if (empty($instagramAccountIds)) {
                throw new \Exception('No Instagram Business account connected');
            }

            $igAccountId = $instagramAccountIds[0]; // Use first connected account

            // Use Instagram Business Discovery API to look up the username
            $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$igAccountId}", [
                'access_token' => $accessToken,
                'fields' => "business_discovery.username({$username}){id,username,name,profile_picture_url,followers_count,follows_count,media_count,biography}",
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $discovery = $data['business_discovery'] ?? null;

                if ($discovery) {
                    return [
                        'valid' => true,
                        'user' => [
                            'id' => $discovery['id'] ?? null,
                            'username' => $discovery['username'] ?? $username,
                            'name' => $discovery['name'] ?? null,
                            'profile_picture' => $discovery['profile_picture_url'] ?? null,
                            'followers' => $discovery['followers_count'] ?? 0,
                            'following' => $discovery['follows_count'] ?? 0,
                            'posts' => $discovery['media_count'] ?? 0,
                            'bio' => $discovery['biography'] ?? null,
                        ],
                    ];
                }
            }

            // Handle errors
            $errorMsg = $response->json('error.message', 'Username not found');
            $errorCode = $response->json('error.code');

            // Error code 110 = user not found
            if ($errorCode === 110) {
                return [
                    'valid' => false,
                    'message' => __('publish.collaborator.user_not_found'),
                ];
            }

            Log::warning('Instagram username validation failed', [
                'username' => $username,
                'error' => $errorMsg,
                'code' => $errorCode,
            ]);

            return [
                'valid' => false,
                'message' => __('publish.collaborator.validation_failed'),
            ];

        } catch (\Exception $e) {
            Log::error('Instagram username validation error', [
                'org_id' => $orgId,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Store a collaborator for future suggestions
     *
     * @param string $orgId Organization UUID
     * @param string $username Instagram username
     * @return bool Success status
     */
    public function storeCollaborator(string $orgId, string $username): bool
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $username = ltrim($username, '@');

            // Check if already exists
            $exists = DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->where('username', $username)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                // Update last used timestamp and increment count
                DB::table('cmis.collaborator_suggestions')
                    ->where('org_id', $orgId)
                    ->where('username', $username)
                    ->update([
                        'use_count' => DB::raw('use_count + 1'),
                        'last_used_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert new collaborator
                DB::table('cmis.collaborator_suggestions')->insert([
                    'id' => Str::uuid()->toString(),
                    'org_id' => $orgId,
                    'username' => $username,
                    'use_count' => 1,
                    'last_used_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return true;

        } catch (\Exception $e) {
            // Silently fail - this is not critical
            Log::warning('Failed to store collaborator', [
                'org_id' => $orgId,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Store multiple collaborators
     *
     * @param string $orgId
     * @param array $usernames
     * @return array Stored usernames
     */
    public function storeMultiple(string $orgId, array $usernames): array
    {
        $stored = [];

        foreach ($usernames as $username) {
            if ($this->storeCollaborator($orgId, $username)) {
                $stored[] = ltrim($username, '@');
            }
        }

        return $stored;
    }

    /**
     * Delete a collaborator suggestion
     *
     * @param string $orgId
     * @param string $username
     * @return bool
     */
    public function deleteCollaborator(string $orgId, string $username): bool
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $username = ltrim($username, '@');

            DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->where('username', $username)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete collaborator', [
                'org_id' => $orgId,
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Search Instagram users by username
     *
     * This is a helper method that could be expanded to search
     * for users beyond the Business Discovery API limits.
     *
     * @param string $orgId
     * @param string $query
     * @return array Search results
     */
    public function searchUsers(string $orgId, string $query): array
    {
        // TODO: Implement user search functionality
        // This could integrate with Instagram's search API or
        // return cached results from past collaborators

        $suggestions = $this->getSuggestions($orgId);

        // Filter suggestions by query
        $query = strtolower(ltrim($query, '@'));
        $filtered = array_filter($suggestions, function ($username) use ($query) {
            return stripos($username, $query) !== false;
        });

        return array_values($filtered);
    }

    /**
     * Get collaborator statistics
     *
     * @param string $orgId
     * @return array Statistics
     */
    public function getStatistics(string $orgId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $totalCollaborators = DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->whereNull('deleted_at')
                ->count();

            $totalUses = DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->whereNull('deleted_at')
                ->sum('use_count');

            $mostUsed = DB::table('cmis.collaborator_suggestions')
                ->where('org_id', $orgId)
                ->whereNull('deleted_at')
                ->orderBy('use_count', 'desc')
                ->limit(5)
                ->get(['username', 'use_count']);

            return [
                'total_collaborators' => $totalCollaborators,
                'total_uses' => $totalUses,
                'most_used' => $mostUsed->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get collaborator statistics', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Validate collaborators array for Instagram post
     *
     * @param array $collaborators
     * @return array Validation result
     */
    public function validateCollaborators(array $collaborators): array
    {
        $errors = [];

        // Maximum 3 collaborators allowed
        if (count($collaborators) > 3) {
            $errors[] = __('publish.collaborator.max_three');
        }

        // Validate each username format
        foreach ($collaborators as $username) {
            $cleanUsername = ltrim($username, '@');
            if (empty($cleanUsername)) {
                $errors[] = __('publish.collaborator.empty_username');
            } elseif (!preg_match('/^[a-zA-Z0-9._]+$/', $cleanUsername)) {
                $errors[] = sprintf(__('publish.collaborator.invalid_format'), $username);
            } elseif (strlen($cleanUsername) > 30) {
                $errors[] = sprintf(__('publish.collaborator.too_long'), $username);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

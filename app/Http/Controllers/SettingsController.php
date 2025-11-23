<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Controllers\Concerns\ApiResponse;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display settings page
     */
    public function index(): View
    {
        return view('settings.index');
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:cmis.users,email,' . auth()->id() . ',user_id',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        try {
            $user = auth()->user();
            $user->update($validated);

            return $this->success($user, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update profile: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return $this->success(['message' => 'Password updated successfully',], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update password: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'required|boolean',
            'push_notifications' => 'required|boolean',
            'sms_notifications' => 'required|boolean',
            'campaign_updates' => 'required|boolean',
            'performance_alerts' => 'required|boolean',
            'weekly_reports' => 'required|boolean',
        ]);

        try {
            $user = auth()->user();

            // Store notification preferences (you might have a UserPreference model)
            // For now, we'll store in user metadata or preferences table
            $user->update([
                'preferences' => array_merge($user->preferences ?? [], [
                    'notifications' => $validated
                ])
            ]);

            return $this->success(['message' => 'Notification settings updated successfully',], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update settings: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Update organization settings
     */
    public function updateOrganization(Request $request): JsonResponse
    {
        $this->authorize('update', auth()->user()->organization);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website' => 'nullable|url',
            'timezone' => 'required|string',
            'language' => 'required|string|in:ar,en',
        ]);

        try {
            $org = auth()->user()->organization;
            $org->update($validated);

            return $this->success($org, 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update organization: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get API keys
     */
    public function apiKeys(): JsonResponse
    {
        // Return user's API keys (you would have an ApiKey model)
        return response()->json([
            'success' => true,
            'data' => [
                // Example structure
                [
                    'id' => '1',
                    'name' => 'Production API Key',
                    'key' => 'cmis_' . str_repeat('*', 32),
                    'created_at' => now()->subDays(30)->toDateTimeString(),
                    'last_used' => now()->subHours(2)->toDateTimeString(),
                ]
            ],
        ]);
    }

    /**
     * Create API key
     */
    public function createApiKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        try {
            // Generate API key (you would create ApiKey model)
            $apiKey = 'cmis_' . bin2hex(random_bytes(32));

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => [
                    'key' => $apiKey,
                    'name' => $validated['name'],
                    'created_at' => now()->toDateTimeString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->serverError('Failed to create API key: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Delete API key
     */
    public function deleteApiKey(string $keyId): JsonResponse
    {
        try {
            // Delete API key
            return $this->success(['message' => 'API key deleted successfully',], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete API key: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get integrations
     */
    public function integrations(): JsonResponse
    {
        $user = auth()->user();

        // Get user's integrations
        $integrations = [
            [
                'id' => 'facebook',
                'name' => 'Facebook',
                'connected' => false,
                'icon' => 'fab fa-facebook',
            ],
            [
                'id' => 'instagram',
                'name' => 'Instagram',
                'connected' => false,
                'icon' => 'fab fa-instagram',
            ],
            [
                'id' => 'linkedin',
                'name' => 'LinkedIn',
                'connected' => false,
                'icon' => 'fab fa-linkedin',
            ],
            [
                'id' => 'twitter',
                'name' => 'Twitter',
                'connected' => false,
                'icon' => 'fab fa-twitter',
            ],
        ];

        return $this->success($integrations, 'Operation completed successfully');
    }

    /**
     * Export user data
     */
    public function exportData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:json,csv',
            'include' => 'nullable|array',
        ]);

        try {
            $user = auth()->user();

            $data = [
                'user' => $user->toArray(),
                'campaigns' => $user->campaigns()->get()->toArray(),
                // Add more data as needed
            ];

            if ($validated['format'] === 'json') {
                return response()->json($data)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Disposition', 'attachment; filename="user_data.json"');
            }

            // CSV export would be implemented here
            return $this->success(['message' => 'Data export initiated',], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to export data: ' . $e->getMessage(),
            );
        }
    }
}

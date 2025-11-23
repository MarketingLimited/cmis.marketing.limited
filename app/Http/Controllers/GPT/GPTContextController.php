<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * GPT Context Controller
 *
 * Provides user and organization context for GPT/ChatGPT integration
 */
class GPTContextController extends Controller
{
    use ApiResponse;

    /**
     * Get user and organization context
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return $this->success([
                'user' => [
                    'id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'organization' => [
                    'id' => $user->current_org_id,
                    'name' => $user->currentOrg?->name,
                    'currency' => $user->currentOrg?->currency ?? 'USD',
                    'locale' => $user->currentOrg?->default_locale ?? 'en',
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('GPT context error: ' . $e->getMessage());
            return $this->serverError('Failed to retrieve context');
        }
    }
}

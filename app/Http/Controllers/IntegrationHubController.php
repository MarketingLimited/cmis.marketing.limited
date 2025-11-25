<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\IntegrationHubService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * IntegrationHubController
 *
 * Handles third-party integrations and webhooks
 * Implements Sprint 6.4: Integration Hub
 */
class IntegrationHubController extends Controller
{
    use ApiResponse;

    protected IntegrationHubService $integrationService;

    public function __construct(IntegrationHubService $integrationService)
    {
        $this->middleware('auth:sanctum');
        $this->integrationService = $integrationService;
    }

    /**
     * Get available integrations
     * GET /api/orgs/{org_id}/integrations/available?category=automation
     */
    public function getAvailableIntegrations(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|in:automation,communication,crm,email,analytics,ecommerce,cms,design,database'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->integrationService->getAvailableIntegrations($request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get integrations', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create integration
     * POST /api/orgs/{org_id}/integrations
     */
    public function createIntegration(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'integration_key' => 'required|string',
            'credentials' => 'nullable|array',
            'config' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'test_connection' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->createIntegration($orgId, $data);
            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create integration', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test integration
     * POST /api/orgs/{org_id}/integrations/{integration_id}/test
     */
    public function testIntegration(string $orgId, string $integrationId): JsonResponse
    {
        try {
            $result = $this->integrationService->testIntegration($integrationId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Test failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create webhook
     * POST /api/orgs/{org_id}/webhooks
     */
    public function createWebhook(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_name' => 'required|string|max:255',
            'webhook_url' => 'required|url',
            'events' => 'required|array',
            'events.*' => 'string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->createWebhook($orgId, $data);
            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create webhook', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate API key
     * POST /api/orgs/{org_id}/api-keys
     */
    public function generateAPIKey(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'in:read,write,delete,admin',
            'expires_in_days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->generateAPIKey($orgId, $data);
            return response()->json($result, $result['success'] ? 201 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to generate API key', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * List API keys
     * GET /api/orgs/{org_id}/api-keys
     */
    public function listAPIKeys(string $orgId): JsonResponse
    {
        try {
            $result = $this->integrationService->listAPIKeys($orgId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list API keys', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Revoke API key
     * DELETE /api/orgs/{org_id}/api-keys/{api_key_id}
     */
    public function revokeAPIKey(string $orgId, string $apiKeyId): JsonResponse
    {
        try {
            $result = $this->integrationService->revokeAPIKey($apiKeyId);
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to revoke API key', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get integration logs
     * GET /api/orgs/{org_id}/integrations/logs?integration_id=uuid&status=success
     */
    public function getIntegrationLogs(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'integration_id' => 'nullable|uuid',
            'status' => 'nullable|in:success,failure',
            'limit' => 'nullable|integer|min:1|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->integrationService->getIntegrationLogs($orgId, $request->all());
            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to get logs', 'error' => $e->getMessage()], 500);
        }
    }
}

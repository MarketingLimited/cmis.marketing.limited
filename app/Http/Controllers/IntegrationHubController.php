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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->integrationService->getAvailableIntegrations($request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get integrations: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->createIntegration($orgId, $data);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create integration: ' . $e->getMessage());
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
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Test failed: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->createWebhook($orgId, $data);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to create webhook: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $userId = $request->user()->user_id ?? null;
            if (!$userId) {
                return $this->error('Authentication required', 401);
            }

            $data = $request->all();
            $data['created_by'] = $userId;

            $result = $this->integrationService->generateAPIKey($orgId, $data);
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->created($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to generate API key: ' . $e->getMessage());
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
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to list API keys: ' . $e->getMessage());
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
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to revoke API key: ' . $e->getMessage());
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
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->integrationService->getIntegrationLogs($orgId, $request->all());
            if (!$result['success']) {
            return $this->serverError($result['message'] ?? 'Operation failed');
        }
        return $this->success($result['data'] ?? $result, $result['message'] ?? 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to get logs: ' . $e->getMessage());
        }
    }
}

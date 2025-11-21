<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\AI\AiQuotaService;
use App\Exceptions\QuotaExceededException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check AI Quota Middleware
 *
 * Validates that user has sufficient AI quota before processing request.
 * Works in conjunction with AiRateLimitMiddleware for comprehensive protection.
 *
 * Usage in routes:
 * Route::post('/ai/generate', [Controller::class, 'generate'])
 *     ->middleware(['auth', 'check.ai.quota:gpt']);
 */
class CheckAiQuotaMiddleware
{
    /**
     * AI Quota Service
     *
     * @var AiQuotaService
     */
    protected AiQuotaService $quotaService;

    /**
     * Constructor
     *
     * @param AiQuotaService $quotaService
     */
    public function __construct(AiQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $aiService  'gpt'|'embeddings'|'image_gen'
     * @param  int  $requestedAmount  Number of requests (default: 1)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(
        Request $request,
        Closure $next,
        string $aiService = 'gpt',
        int $requestedAmount = 1
    ): Response {
        // Require authentication
        if (!auth()->check()) {
            return response()->json([
                'error' => 'unauthenticated',
                'message' => 'You must be logged in to use AI features',
            ], 401);
        }

        // Get user and organization
        $user = auth()->user();
        $orgId = $user->org_id;
        $userId = $user->id;

        try {
            // Check if quota is available
            $this->quotaService->checkQuota(
                $orgId,
                $userId,
                $aiService,
                $requestedAmount
            );

            // Get current quota status for response headers
            $quotaStatus = $this->quotaService->getQuotaStatus($orgId, $userId);

            // Process request
            $response = $next($request);

            // Add quota info to response headers
            $this->addQuotaHeaders($response, $quotaStatus, $aiService);

            return $response;

        } catch (QuotaExceededException $e) {
            // Quota exceeded - return appropriate response
            return $this->quotaExceededResponse($e, $request);
        } catch (\Exception $e) {
            // Unexpected error
            \Illuminate\Support\Facades\Log::error('AI quota check failed', [
                'user_id' => $userId,
                'org_id' => $orgId,
                'service' => $aiService,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'quota_check_failed',
                'message' => 'Failed to verify AI quota. Please try again.',
            ], 500);
        }
    }

    /**
     * Generate quota exceeded response
     *
     * @param QuotaExceededException $exception
     * @param Request $request
     * @return Response
     */
    protected function quotaExceededResponse(
        QuotaExceededException $exception,
        Request $request
    ): Response {
        $quotaType = $exception->getQuotaType();
        $context = $exception->getContext();

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'quota_exceeded',
                'message' => $exception->getMessage(),
                'quota_type' => $quotaType,
                'upgrade_url' => route('billing.upgrade'),
                'current_tier' => auth()->user()->organization->subscription_tier ?? 'free',
                'details' => [
                    'type' => $quotaType,
                    'message' => $this->getQuotaMessage($quotaType),
                ],
            ], 429);
        }

        return response()->view('errors.quota-exceeded', [
            'exception' => $exception,
            'quotaType' => $quotaType,
            'upgradeUrl' => route('billing.upgrade'),
        ], 429);
    }

    /**
     * Get user-friendly quota message
     *
     * @param string $quotaType
     * @return string
     */
    protected function getQuotaMessage(string $quotaType): string
    {
        $messages = [
            'daily' => 'You have reached your daily AI usage limit. Upgrade your plan or wait until tomorrow.',
            'monthly' => 'You have reached your monthly AI usage limit. Upgrade your plan for more quota.',
            'cost' => 'You have reached your monthly cost limit. Upgrade your plan to continue using AI features.',
        ];

        return $messages[$quotaType] ?? 'You have exceeded your AI usage quota.';
    }

    /**
     * Add quota information to response headers
     *
     * @param Response $response
     * @param array $quotaStatus
     * @param string $aiService
     * @return void
     */
    protected function addQuotaHeaders(
        Response $response,
        array $quotaStatus,
        string $aiService
    ): void {
        if (!isset($quotaStatus[$aiService])) {
            return;
        }

        $quota = $quotaStatus[$aiService];

        // Add daily quota headers
        $response->headers->set(
            'X-AI-Quota-Daily-Limit',
            $quota['daily']['limit']
        );
        $response->headers->set(
            'X-AI-Quota-Daily-Used',
            $quota['daily']['used']
        );
        $response->headers->set(
            'X-AI-Quota-Daily-Remaining',
            $quota['daily']['remaining']
        );

        // Add monthly quota headers
        $response->headers->set(
            'X-AI-Quota-Monthly-Limit',
            $quota['monthly']['limit']
        );
        $response->headers->set(
            'X-AI-Quota-Monthly-Used',
            $quota['monthly']['used']
        );
        $response->headers->set(
            'X-AI-Quota-Monthly-Remaining',
            $quota['monthly']['remaining']
        );

        // Add cost headers if available
        if (isset($quota['cost']['used'])) {
            $response->headers->set(
                'X-AI-Cost-Month',
                number_format($quota['cost']['used'], 2)
            );

            if ($quota['cost']['limit']) {
                $response->headers->set(
                    'X-AI-Cost-Limit',
                    number_format($quota['cost']['limit'], 2)
                );
            }
        }

        // Add tier information
        $response->headers->set('X-AI-Tier', $quota['tier']);
    }
}

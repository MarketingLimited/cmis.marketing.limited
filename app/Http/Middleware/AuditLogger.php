<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuditLogger
{
    /**
     * Handle an incoming request and log it to audit system
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$categories): Response
    {
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds

        // Determine category
        $category = !empty($categories) ? $categories[0] : $this->determineCategory($request);

        // Only log if category is valid
        if (in_array($category, ['task', 'knowledge', 'security', 'system'])) {
            $this->logToAudit($request, $response, $category, $duration);
        }

        return $response;
    }

    /**
     * Determine the category based on request
     */
    private function determineCategory(Request $request): string
    {
        $path = $request->path();

        // Security-related endpoints
        if (str_contains($path, 'auth') || str_contains($path, 'login') || str_contains($path, 'logout')) {
            return 'security';
        }

        // Knowledge-related endpoints
        if (str_contains($path, 'knowledge') || str_contains($path, 'cmis')) {
            return 'knowledge';
        }

        // Task-related endpoints
        if (str_contains($path, 'campaign') || str_contains($path, 'post') || str_contains($path, 'workflow')) {
            return 'task';
        }

        // Default to system
        return 'system';
    }

    /**
     * Log request to audit system
     */
    private function logToAudit(Request $request, Response $response, string $category, float $duration): void
    {
        try {
            $user = $request->user();
            $actor = $user ? $user->email : ($request->ip() ?? 'anonymous');

            // Determine action
            $action = $this->getAction($request, $response);

            // Build context
            $context = [
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $duration,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            // Add request data for POST/PUT/PATCH (excluding sensitive fields)
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                $requestData = $request->except(['password', 'password_confirmation', 'token', 'api_key']);
                if (!empty($requestData)) {
                    $context['request_data'] = $requestData;
                }
            }

            // Log to database
            DB::table('cmis_audit.activity_log')->insert([
                'actor' => $actor,
                'action' => $action,
                'context' => json_encode($context),
                'category' => $category,
                'created_at' => now()
            ]);

        } catch (\Exception $e) {
            // Silently fail - don't break the application if audit logging fails
            logger()->error('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Get action name from request and response
     */
    private function getAction(Request $request, Response $response): string
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Failed requests
        if ($statusCode >= 400) {
            if ($statusCode === 401 || $statusCode === 403) {
                return 'access_denied';
            }
            return 'request_failed';
        }

        // Authentication endpoints
        if (str_contains($path, 'login')) {
            return $statusCode === 200 ? 'login_success' : 'login_failed';
        }

        if (str_contains($path, 'logout')) {
            return 'logout';
        }

        // CRUD operations
        if ($method === 'POST') {
            return 'create';
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            return 'update';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        if ($method === 'GET') {
            return 'read';
        }

        return 'unknown_action';
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="CMIS API Documentation",
 *     description="Cognitive Marketing Information System (CMIS) - RESTful API for campaign management, platform integrations, and AI-powered content generation.",
 *     @OA\Contact(
 *         email="support@cmis.marketing",
 *         name="CMIS Support"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="CMIS API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication. Include token in Authorization header as: Bearer {token}"
 * )
 */
class SwaggerController extends Controller
{
    use ApiResponse;

    /**
     * Display Swagger UI for interactive API documentation.
     */
    public function ui(): View
    {
        return view('api.swagger-ui');
    }

    /**
     * Return OpenAPI specification in JSON format.
     */
    public function json(): JsonResponse
    {
        $spec = $this->generateOpenApiSpec();

        return $this->success($spec, 'Retrieved successfully');
    }

    /**
     * Return OpenAPI specification in YAML format.
     */
    public function yaml()
    {
        $spec = $this->generateOpenApiSpec();

        // Convert to YAML (simple implementation)
        $yaml = $this->arrayToYaml($spec);

        return response($yaml, 200, [
            'Content-Type' => 'application/x-yaml',
        ]);
    }

    /**
     * Generate OpenAPI specification from routes.
     *
     * This is a simplified auto-generator. In production, use L5-Swagger
     * or similar package for full OpenAPI 3.0 support.
     */
    protected function generateOpenApiSpec(): array
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('swagger.title', 'CMIS API'),
                'version' => config('swagger.version', '1.0.0'),
                'description' => config('swagger.description', ''),
                'contact' => config('swagger.contact', []),
                'license' => config('swagger.license', []),
            ],
            'servers' => config('swagger.servers', []),
            'paths' => $this->generatePaths(),
            'components' => [
                'securitySchemes' => config('swagger.security_schemes', []),
                'schemas' => $this->generateSchemas(),
            ],
            'security' => [
                ['sanctum' => []],
            ],
        ];

        return $spec;
    }

    /**
     * Generate paths from registered API routes.
     */
    protected function generatePaths(): array
    {
        $paths = [];

        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $uri = $route->uri();

            // Only include API routes
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            // Skip documentation routes
            if (str_contains($uri, 'documentation') || str_contains($uri, 'openapi')) {
                continue;
            }

            $methods = $route->methods();
            $path = '/' . str_replace('api/', '', $uri);

            foreach ($methods as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $paths[$path][$method] = $this->generateOperation($route, $method);
            }
        }

        return $paths;
    }

    /**
     * Generate operation details for a route.
     */
    protected function generateOperation($route, string $method): array
    {
        $action = $route->getActionName();
        $uri = $route->uri();

        // Extract tags from URI
        $tags = $this->extractTags($uri);

        // Generate summary from URI
        $summary = $this->generateSummary($uri, $method);

        return [
            'tags' => $tags,
            'summary' => $summary,
            'operationId' => $this->generateOperationId($uri, $method),
            'parameters' => $this->extractParameters($uri),
            'responses' => [
                '200' => [
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'message' => ['type' => 'string'],
                                    'data' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
                '401' => [
                    'description' => 'Unauthorized',
                ],
                '404' => [
                    'description' => 'Resource not found',
                ],
            ],
            'security' => [
                ['sanctum' => []],
            ],
        ];
    }

    /**
     * Extract tags from URI.
     */
    protected function extractTags(string $uri): array
    {
        // Extract first segment after 'api/'
        $segments = explode('/', str_replace('api/', '', $uri));

        if (!empty($segments[0])) {
            return [ucfirst($segments[0])];
        }

        return ['General'];
    }

    /**
     * Generate operation summary from URI and method.
     */
    protected function generateSummary(string $uri, string $method): string
    {
        $segments = explode('/', str_replace('api/', '', $uri));
        $resource = $segments[0] ?? 'resource';

        return match (strtoupper($method)) {
            'GET' => str_contains($uri, '{') ? 'Get specific ' . $resource : 'List ' . $resource,
            'POST' => 'Create ' . $resource,
            'PUT', 'PATCH' => 'Update ' . $resource,
            'DELETE' => 'Delete ' . $resource,
            default => 'Operate on ' . $resource,
        };
    }

    /**
     * Generate operation ID from URI and method.
     */
    protected function generateOperationId(string $uri, string $method): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9]/', '_', str_replace('api/', '', $uri));

        return strtolower($method) . '_' . $cleaned;
    }

    /**
     * Extract parameters from URI.
     */
    protected function extractParameters(string $uri): array
    {
        $parameters = [];

        // Extract path parameters
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'string',
                ],
                'description' => 'The ' . str_replace('_', ' ', $param),
            ];
        }

        return $parameters;
    }

    /**
     * Generate common schemas for responses.
     */
    protected function generateSchemas(): array
    {
        return [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => false],
                    'message' => ['type' => 'string', 'example' => 'An error occurred'],
                    'code' => ['type' => 'string', 'example' => 'ERROR_CODE'],
                    'errors' => ['type' => 'object'],
                ],
            ],
            'Success' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean', 'example' => true],
                    'message' => ['type' => 'string', 'example' => 'Operation successful'],
                    'data' => ['type' => 'object'],
                ],
            ],
            'PaginatedResponse' => [
                'type' => 'object',
                'properties' => [
                    'success' => ['type' => 'boolean'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                    'meta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                        ],
                    ],
                    'links' => [
                        'type' => 'object',
                        'properties' => [
                            'first' => ['type' => 'string'],
                            'last' => ['type' => 'string'],
                            'prev' => ['type' => 'string', 'nullable' => true],
                            'next' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Convert array to YAML format (simple implementation).
     */
    protected function arrayToYaml(array $array, int $indent = 0): string
    {
        $yaml = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $yaml .= $spaces . $key . ":\n";
                $yaml .= $this->arrayToYaml($value, $indent + 1);
            } else {
                $yaml .= $spaces . $key . ': ' . $this->formatYamlValue($value) . "\n";
            }
        }

        return $yaml;
    }

    /**
     * Format value for YAML output.
     */
    protected function formatYamlValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#'))) {
            return '"' . $value . '"';
        }

        return (string) $value;
    }
}

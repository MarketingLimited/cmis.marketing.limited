<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RLSViolationException extends Exception
{
    /**
     * The resource type that was attempted to access.
     */
    protected string $resourceType;

    /**
     * The resource ID that was attempted to access.
     */
    protected ?string $resourceId;

    /**
     * The organization ID that owns the resource.
     */
    protected ?string $actualOrgId;

    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $resourceType = 'resource',
        ?string $resourceId = null,
        ?string $actualOrgId = null,
        string $message = null
    ) {
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->actualOrgId = $actualOrgId;

        $defaultMessage = $message ?? $this->buildMessage();

        parent::__construct($defaultMessage, 403);
    }

    /**
     * Build a user-friendly error message.
     */
    protected function buildMessage(): string
    {
        if ($this->actualOrgId) {
            return sprintf(
                'This %s belongs to a different organization. Please switch to the correct organization to access it.',
                $this->resourceType
            );
        }

        return sprintf(
            'This %s was not found or you do not have permission to access it.',
            $this->resourceType
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'RLS_VIOLATION',
            'resource_type' => $this->resourceType,
        ];

        // Add helpful hints
        if ($this->actualOrgId) {
            $response['hint'] = 'This resource exists but belongs to another organization. Switch organizations to access it.';
            $response['suggestion'] = 'Use POST /api/user/switch-organization to switch to the correct organization.';
        }

        if ($this->resourceId) {
            $response['resource_id'] = $this->resourceId;
        }

        return response()->json($response, 403);
    }

    /**
     * Get the resource type.
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * Get the actual organization ID.
     */
    public function getActualOrgId(): ?string
    {
        return $this->actualOrgId;
    }
}

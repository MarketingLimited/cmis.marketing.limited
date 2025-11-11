<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrgAccessDeniedException extends Exception
{
    /**
     * The organization ID that access was denied to.
     */
    protected $orgId;

    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Access denied to organization', $orgId = null)
    {
        parent::__construct($message);
        $this->orgId = $orgId;
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        // يمكن إضافة logging هنا
        \Log::warning('Organization access denied', [
            'message' => $this->getMessage(),
            'org_id' => $this->orgId,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'Access Denied',
            'message' => $this->getMessage(),
            'org_id' => $this->orgId,
        ], 403);
    }
}

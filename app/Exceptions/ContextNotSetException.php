<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContextNotSetException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Database context not set')
    {
        parent::__construct($message);
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        \Log::error('Database context not set', [
            'message' => $this->getMessage(),
            'user_id' => auth()->id(),
            'route' => request()->route()?->getName(),
            'url' => request()->fullUrl(),
        ]);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => 'Context Error',
            'message' => $this->getMessage(),
            'hint' => 'Organization ID is required for this operation',
        ], 400);
    }
}

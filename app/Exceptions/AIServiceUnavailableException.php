<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI services are unavailable
 *
 * This allows graceful degradation when external AI providers (Gemini, OpenAI, etc.)
 * are down or unreachable.
 */
class AIServiceUnavailableException extends Exception
{
    public function __construct(
        string $message = 'AI service is temporarily unavailable',
        public readonly ?string $provider = null,
        public readonly ?string $suggestedAction = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, 503, $previous);
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        $response = [
            'success' => false,
            'error' => 'AI Service Unavailable',
            'message' => $this->getMessage(),
            'provider' => $this->provider,
            'suggested_action' => $this->suggestedAction ?? 'You can still create content manually. AI features will be available when the service recovers.',
            'can_retry' => true,
            'retry_after' => 60, // seconds
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($response, 503);
        }

        return back()
            ->with('error', $this->getMessage())
            ->with('ai_unavailable', true)
            ->with('suggested_action', $response['suggested_action']);
    }
}

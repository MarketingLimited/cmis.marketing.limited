<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wizard Step Exception
 *
 * Thrown when wizard step validation fails or flow is interrupted.
 * Part of Phase 1B - Campaign Wizard Implementation (2025-11-21)
 */
class WizardStepException extends Exception
{
    protected array $context;

    public function __construct(
        string $message = 'Wizard step error',
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get exception context data
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get current step from context
     */
    public function getCurrentStep(): ?int
    {
        return $this->context['current_step'] ?? null;
    }

    /**
     * Get session ID from context
     */
    public function getSessionId(): ?string
    {
        return $this->context['session_id'] ?? null;
    }

    /**
     * Get missing fields from context
     */
    public function getMissingFields(): array
    {
        return $this->context['missing_fields'] ?? [];
    }

    /**
     * Report the exception (logging)
     */
    public function report(): void
    {
        logger()->warning('Wizard step exception', [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    /**
     * Render the exception as HTTP response
     */
    public function render(Request $request): JsonResponse
    {
        $response = [
            'error' => true,
            'message' => $this->getMessage(),
            'wizard_error' => true,
        ];

        // Add context data for debugging (non-production)
        if (config('app.debug')) {
            $response['context'] = $this->context;
        }

        // Add helpful information for frontend
        if ($currentStep = $this->getCurrentStep()) {
            $response['current_step'] = $currentStep;
        }

        if ($missingFields = $this->getMissingFields()) {
            $response['missing_fields'] = $missingFields;
            $response['message'] = 'Please complete all required fields: ' . implode(', ', $missingFields);
        }

        return response()->json($response, 422);
    }
}

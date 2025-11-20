<?php

namespace App\Exceptions;

use Exception;

class FeatureDisabledException extends Exception
{
    /**
     * Report the exception
     */
    public function report(): void
    {
        // Log the exception for monitoring
    }

    /**
     * Render the exception as an HTTP response
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Feature not available',
                'message' => $this->getMessage(),
                'code' => 'FEATURE_DISABLED',
            ], 403);
        }

        return response()->view('errors.feature-disabled', [
            'message' => $this->getMessage(),
        ], 403);
    }
}

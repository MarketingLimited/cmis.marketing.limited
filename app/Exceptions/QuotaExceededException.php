<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Quota Exceeded Exception
 *
 * Thrown when user/org exceeds their AI usage quota
 */
class QuotaExceededException extends Exception
{
    /**
     * Quota type that was exceeded
     *
     * @var string 'daily', 'monthly', 'cost'
     */
    protected string $quotaType;

    /**
     * Additional context data
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string $message
     * @param string $quotaType
     * @param array $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'AI usage quota exceeded',
        string $quotaType = 'daily',
        array $context = [],
        int $code = 429,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->quotaType = $quotaType;
        $this->context = $context;
    }

    /**
     * Get the quota type
     *
     * @return string
     */
    public function getQuotaType(): string
    {
        return $this->quotaType;
    }

    /**
     * Get context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception as an HTTP response
     *
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'quota_exceeded',
                'message' => $this->getMessage(),
                'quota_type' => $this->quotaType,
                'context' => $this->context,
                'upgrade_url' => route('billing.upgrade'),
            ], 429);
        }

        return response()->view('errors.quota-exceeded', [
            'message' => $this->getMessage(),
            'quotaType' => $this->quotaType,
            'context' => $this->context,
        ], 429);
    }

    /**
     * Report the exception
     *
     * @return bool|null
     */
    public function report(): ?bool
    {
        // Log quota exceeded events for monitoring
        \Illuminate\Support\Facades\Log::warning('AI Quota Exceeded', [
            'message' => $this->getMessage(),
            'quota_type' => $this->quotaType,
            'context' => $this->context,
            'user_id' => auth()->id(),
            'org_id' => session('current_org_id'),
        ]);

        // Return false to prevent default exception reporting
        return false;
    }
}

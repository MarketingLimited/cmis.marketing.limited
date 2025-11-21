<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Services\AI\AiQuotaService;

/**
 * AI Quota Widget Component
 *
 * Displays user's AI quota status in dashboard or sidebar.
 *
 * Usage in Blade:
 * <x-ai-quota-widget service="gpt" />
 * <x-ai-quota-widget service="embeddings" :compact="true" />
 */
class AiQuotaWidget extends Component
{
    /**
     * AI service to display quota for
     *
     * @var string
     */
    public string $service;

    /**
     * Compact mode (smaller display)
     *
     * @var bool
     */
    public bool $compact;

    /**
     * Quota data
     *
     * @var array|null
     */
    public ?array $quota = null;

    /**
     * Create a new component instance.
     *
     * @param string $service 'gpt' or 'embeddings'
     * @param bool $compact
     */
    public function __construct(string $service = 'gpt', bool $compact = false)
    {
        $this->service = $service;
        $this->compact = $compact;

        if (auth()->check()) {
            $this->loadQuotaData();
        }
    }

    /**
     * Load quota data for current user
     *
     * @return void
     */
    protected function loadQuotaData(): void
    {
        try {
            $quotaService = app(AiQuotaService::class);
            $user = auth()->user();

            $status = $quotaService->getQuotaStatus($user->org_id, $user->id);

            if (isset($status[$this->service])) {
                $this->quota = $status[$this->service];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load AI quota', [
                'user_id' => auth()->id(),
                'service' => $this->service,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ai-quota-widget');
    }

    /**
     * Get progress bar color based on usage percentage
     *
     * @param float $percentage
     * @return string
     */
    public function getProgressColor(float $percentage): string
    {
        if ($percentage >= 95) {
            return 'red'; // Critical
        } elseif ($percentage >= 80) {
            return 'orange'; // Warning
        } elseif ($percentage >= 50) {
            return 'yellow'; // Moderate
        } else {
            return 'green'; // Good
        }
    }

    /**
     * Get service display name
     *
     * @return string
     */
    public function getServiceName(): string
    {
        $names = [
            'gpt' => 'AI Content Generation',
            'embeddings' => 'Semantic Search',
        ];

        return $names[$this->service] ?? ucfirst($this->service);
    }

    /**
     * Check if user should see upgrade prompt
     *
     * @return bool
     */
    public function shouldShowUpgrade(): bool
    {
        if (!$this->quota) {
            return false;
        }

        $dailyPercentage = $this->quota['daily']['percentage'] ?? 0;
        $monthlyPercentage = $this->quota['monthly']['percentage'] ?? 0;

        // Show upgrade if over 80% on either daily or monthly
        return $dailyPercentage >= 80 || $monthlyPercentage >= 80;
    }
}

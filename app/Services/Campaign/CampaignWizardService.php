<?php

namespace App\Services\Campaign;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\WizardStepException;

/**
 * Campaign Wizard Service
 *
 * Manages multi-step campaign creation process with validation,
 * draft saving, and progress tracking.
 *
 * Part of Phase 2 - UX improvements (2025-11-21)
 *
 * Steps:
 * 1. Basics - Name, objective, budget
 * 2. Targeting - Audience definition
 * 3. Creative - Ad content and media
 * 4. Review - Final validation and publish
 */
class CampaignWizardService
{
    /**
     * Wizard steps configuration
     */
    protected array $steps = [
        1 => [
            'key' => 'basics',
            'title' => 'Campaign Basics',
            'description' => 'Define your campaign name, objective, and budget',
            'required_fields' => ['name', 'objective', 'budget_total', 'start_date'],
            'optional_fields' => ['description', 'end_date', 'budget_daily'],
        ],
        2 => [
            'key' => 'targeting',
            'title' => 'Target Audience',
            'description' => 'Define who you want to reach',
            'required_fields' => ['audience_type'],
            'optional_fields' => [
                'age_min', 'age_max', 'gender', 'locations',
                'interests', 'behaviors', 'custom_audience_id',
            ],
        ],
        3 => [
            'key' => 'creative',
            'title' => 'Ad Creative',
            'description' => 'Create your ad content',
            'required_fields' => ['ad_format', 'primary_text'],
            'optional_fields' => [
                'headline', 'description', 'call_to_action',
                'media_urls', 'link_url', 'use_ai_generation',
            ],
        ],
        4 => [
            'key' => 'review',
            'title' => 'Review & Launch',
            'description' => 'Review everything before publishing',
            'required_fields' => [],
            'optional_fields' => ['send_for_approval', 'schedule_publish'],
        ],
    ];

    /**
     * Start new wizard session
     *
     * @param string $userId
     * @param string $orgId
     * @return array
     */
    public function startWizard(string $userId, string $orgId): array
    {
        $sessionId = \Illuminate\Support\Str::uuid()->toString();

        $session = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'org_id' => $orgId,
            'current_step' => 1,
            'completed_steps' => [],
            'data' => [],
            'started_at' => now()->toIso8601String(),
            'last_updated' => now()->toIso8601String(),
        ];

        // Save to cache (1 hour expiry)
        Cache::put($this->getCacheKey($sessionId), $session, 3600);

        return $session;
    }

    /**
     * Get wizard session
     *
     * @param string $sessionId
     * @return array|null
     */
    public function getSession(string $sessionId): ?array
    {
        return Cache::get($this->getCacheKey($sessionId));
    }

    /**
     * Update wizard step data
     *
     * @param string $sessionId
     * @param int $step
     * @param array $data
     * @return array
     * @throws WizardStepException
     */
    public function updateStep(string $sessionId, int $step, array $data): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new WizardStepException('Wizard session not found or expired');
        }

        if (!isset($this->steps[$step])) {
            throw new WizardStepException('Invalid wizard step');
        }

        // Validate step data
        $this->validateStepData($step, $data);

        // Merge data into session
        $session['data'] = array_merge($session['data'], $data);

        // Mark step as completed if all required fields present
        if ($this->isStepComplete($step, $session['data'])) {
            if (!in_array($step, $session['completed_steps'])) {
                $session['completed_steps'][] = $step;
            }
        }

        $session['last_updated'] = now()->toIso8601String();

        // Save updated session
        Cache::put($this->getCacheKey($sessionId), $session, 3600);

        return $session;
    }

    /**
     * Move to next step
     *
     * @param string $sessionId
     * @return array
     * @throws WizardStepException
     */
    public function nextStep(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new WizardStepException('Wizard session not found');
        }

        $currentStep = $session['current_step'];

        // Validate current step is complete
        if (!$this->isStepComplete($currentStep, $session['data'])) {
            throw new WizardStepException(
                'Please complete all required fields before proceeding',
                ['missing_fields' => $this->getMissingFields($currentStep, $session['data'])]
            );
        }

        // Move to next step
        $nextStep = $currentStep + 1;

        if ($nextStep > count($this->steps)) {
            throw new WizardStepException('Already at final step');
        }

        $session['current_step'] = $nextStep;
        $session['last_updated'] = now()->toIso8601String();

        Cache::put($this->getCacheKey($sessionId), $session, 3600);

        return $session;
    }

    /**
     * Go back to previous step
     *
     * @param string $sessionId
     * @return array
     * @throws WizardStepException
     */
    public function previousStep(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new WizardStepException('Wizard session not found');
        }

        if ($session['current_step'] <= 1) {
            throw new WizardStepException('Already at first step');
        }

        $session['current_step'] = $session['current_step'] - 1;
        $session['last_updated'] = now()->toIso8601String();

        Cache::put($this->getCacheKey($sessionId), $session, 3600);

        return $session;
    }

    /**
     * Complete wizard and create campaign
     *
     * @param string $sessionId
     * @return object Campaign
     * @throws WizardStepException
     */
    public function complete(string $sessionId): object
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new WizardStepException('Wizard session not found');
        }

        // Validate all steps complete
        foreach ($this->steps as $stepNum => $stepConfig) {
            if (!in_array($stepNum, $session['completed_steps'])) {
                throw new WizardStepException(
                    "Step {$stepNum} ({$stepConfig['title']}) is not complete"
                );
            }
        }

        // Create campaign from session data
        $campaign = $this->createCampaignFromSession($session);

        // Clear session
        Cache::forget($this->getCacheKey($sessionId));

        return $campaign;
    }

    /**
     * Save as draft
     *
     * @param string $sessionId
     * @return object Draft campaign
     */
    public function saveDraft(string $sessionId): object
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new WizardStepException('Wizard session not found');
        }

        $campaignId = \Illuminate\Support\Str::uuid()->toString();

        // Create draft campaign
        DB::table('cmis.campaigns')->insert([
            'id' => $campaignId,
            'org_id' => $session['org_id'],
            'name' => $session['data']['name'] ?? 'Draft Campaign',
            'status' => 'draft',
            'objective' => $session['data']['objective'] ?? 'awareness',
            'budget_total' => $session['data']['budget_total'] ?? 0,
            'budget_daily' => $session['data']['budget_daily'] ?? null,
            'start_date' => $session['data']['start_date'] ?? now(),
            'wizard_session_id' => $sessionId,
            'wizard_data' => json_encode($session['data']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('cmis.campaigns')->where('id', $campaignId)->first();
    }

    /**
     * Validate step data
     *
     * @param int $step
     * @param array $data
     * @throws WizardStepException
     */
    protected function validateStepData(int $step, array $data): void
    {
        $stepConfig = $this->steps[$step];

        // Basic validation for required fields if completing step
        // (More detailed validation in FormRequests)
    }

    /**
     * Check if step is complete
     *
     * @param int $step
     * @param array $data
     * @return bool
     */
    protected function isStepComplete(int $step, array $data): bool
    {
        $stepConfig = $this->steps[$step];

        foreach ($stepConfig['required_fields'] as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get missing required fields for step
     *
     * @param int $step
     * @param array $data
     * @return array
     */
    protected function getMissingFields(int $step, array $data): array
    {
        $stepConfig = $this->steps[$step];
        $missing = [];

        foreach ($stepConfig['required_fields'] as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Create campaign from wizard session
     *
     * @param array $session
     * @return object
     */
    protected function createCampaignFromSession(array $session): object
    {
        $data = $session['data'];
        $campaignId = \Illuminate\Support\Str::uuid()->toString();

        DB::table('cmis.campaigns')->insert([
            'id' => $campaignId,
            'org_id' => $session['org_id'],
            'name' => $data['name'],
            'status' => 'pending_review',
            'objective' => $data['objective'],
            'budget_total' => $data['budget_total'],
            'budget_daily' => $data['budget_daily'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('cmis.campaigns')->where('id', $campaignId)->first();
    }

    /**
     * Get cache key for session
     *
     * @param string $sessionId
     * @return string
     */
    protected function getCacheKey(string $sessionId): string
    {
        return "campaign_wizard:session:{$sessionId}";
    }

    /**
     * Get wizard steps configuration
     *
     * @return array
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get progress percentage
     *
     * @param string $sessionId
     * @return int
     */
    public function getProgress(string $sessionId): int
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return 0;
        }

        $totalSteps = count($this->steps);
        $completedSteps = count($session['completed_steps']);

        return $totalSteps > 0 ? (int) (($completedSteps / $totalSteps) * 100) : 0;
    }
}

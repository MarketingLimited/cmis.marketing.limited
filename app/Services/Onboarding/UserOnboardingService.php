<?php

namespace App\Services\Onboarding;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Core\User;

/**
 * User Onboarding Service
 *
 * Manages the user onboarding experience, tracking progress
 * and guiding new users through essential features.
 *
 * Onboarding Steps:
 * 1. Welcome & Profile Setup
 * 2. Connect Meta Account
 * 3. Create First Campaign
 * 4. Explore Dashboard
 * 5. Complete!
 */
class UserOnboardingService
{
    /**
     * Onboarding steps configuration
     */
    protected array $steps = [
        'welcome' => [
            'order' => 1,
            'title' => 'Welcome to CMIS',
            'description' => 'Get started with our AI-powered marketing platform',
            'icon' => 'wave',
            'required' => true,
        ],
        'profile_setup' => [
            'order' => 2,
            'title' => 'Complete Your Profile',
            'description' => 'Tell us about yourself and your business',
            'icon' => 'user',
            'required' => true,
        ],
        'connect_meta' => [
            'order' => 3,
            'title' => 'Connect Meta Account',
            'description' => 'Link your Facebook and Instagram accounts',
            'icon' => 'link',
            'required' => false,
        ],
        'first_campaign' => [
            'order' => 4,
            'title' => 'Create Your First Campaign',
            'description' => 'Launch your first AI-powered ad campaign',
            'icon' => 'rocket',
            'required' => false,
        ],
        'explore_dashboard' => [
            'order' => 5,
            'title' => 'Explore Dashboard',
            'description' => 'Tour the main features and analytics',
            'icon' => 'compass',
            'required' => false,
        ],
    ];

    /**
     * Get onboarding progress for a user
     *
     * @param string $userId
     * @return array
     */
    public function getProgress(string $userId): array
    {
        $cacheKey = "onboarding:progress:{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $progress = DB::table('cmis.user_onboarding_progress')
                ->where('user_id', $userId)
                ->first();

            if (!$progress) {
                // Initialize onboarding for new user
                $this->initializeOnboarding($userId);
                $progress = DB::table('cmis.user_onboarding_progress')
                    ->where('user_id', $userId)
                    ->first();
            }

            $completedSteps = json_decode($progress->completed_steps ?? '[]', true);
            $skippedSteps = json_decode($progress->skipped_steps ?? '[]', true);

            $totalSteps = count($this->steps);
            $completed = count($completedSteps);
            $percentage = $totalSteps > 0 ? round(($completed / $totalSteps) * 100) : 0;

            // Determine current step
            $currentStep = null;
            foreach ($this->steps as $stepKey => $stepConfig) {
                if (!in_array($stepKey, $completedSteps) && !in_array($stepKey, $skippedSteps)) {
                    $currentStep = $stepKey;
                    break;
                }
            }

            return [
                'is_completed' => $progress->is_completed ?? false,
                'current_step' => $currentStep,
                'completed_steps' => $completedSteps,
                'skipped_steps' => $skippedSteps,
                'percentage' => $percentage,
                'steps_completed' => $completed,
                'total_steps' => $totalSteps,
                'started_at' => $progress->started_at,
                'completed_at' => $progress->completed_at,
                'steps' => $this->getStepsWithStatus($completedSteps, $skippedSteps),
            ];
        });
    }

    /**
     * Mark a step as completed
     *
     * @param string $userId
     * @param string $stepKey
     * @return bool
     */
    public function completeStep(string $userId, string $stepKey): bool
    {
        if (!isset($this->steps[$stepKey])) {
            return false;
        }

        $progress = DB::table('cmis.user_onboarding_progress')
            ->where('user_id', $userId)
            ->first();

        if (!$progress) {
            $this->initializeOnboarding($userId);
            $progress = DB::table('cmis.user_onboarding_progress')
                ->where('user_id', $userId)
                ->first();
        }

        $completedSteps = json_decode($progress->completed_steps ?? '[]', true);

        if (!in_array($stepKey, $completedSteps)) {
            $completedSteps[] = $stepKey;

            $updates = [
                'completed_steps' => json_encode($completedSteps),
                'updated_at' => now(),
            ];

            // Check if all steps completed
            if (count($completedSteps) >= count($this->steps)) {
                $updates['is_completed'] = true;
                $updates['completed_at'] = now();
            }

            DB::table('cmis.user_onboarding_progress')
                ->where('user_id', $userId)
                ->update($updates);

            // Clear cache
            Cache::forget("onboarding:progress:{$userId}");

            // Log achievement
            $this->logOnboardingEvent($userId, 'step_completed', ['step' => $stepKey]);

            return true;
        }

        return false;
    }

    /**
     * Skip a step
     *
     * @param string $userId
     * @param string $stepKey
     * @return bool
     */
    public function skipStep(string $userId, string $stepKey): bool
    {
        if (!isset($this->steps[$stepKey]) || $this->steps[$stepKey]['required']) {
            return false; // Cannot skip required steps
        }

        $progress = DB::table('cmis.user_onboarding_progress')
            ->where('user_id', $userId)
            ->first();

        $skippedSteps = json_decode($progress->skipped_steps ?? '[]', true);

        if (!in_array($stepKey, $skippedSteps)) {
            $skippedSteps[] = $stepKey;

            DB::table('cmis.user_onboarding_progress')
                ->where('user_id', $userId)
                ->update([
                    'skipped_steps' => json_encode($skippedSteps),
                    'updated_at' => now(),
                ]);

            Cache::forget("onboarding:progress:{$userId}");

            $this->logOnboardingEvent($userId, 'step_skipped', ['step' => $stepKey]);

            return true;
        }

        return false;
    }

    /**
     * Reset onboarding for a user
     *
     * @param string $userId
     * @return void
     */
    public function resetOnboarding(string $userId): void
    {
        DB::table('cmis.user_onboarding_progress')
            ->where('user_id', $userId)
            ->update([
                'completed_steps' => json_encode([]),
                'skipped_steps' => json_encode([]),
                'is_completed' => false,
                'completed_at' => null,
                'updated_at' => now(),
            ]);

        Cache::forget("onboarding:progress:{$userId}");

        $this->logOnboardingEvent($userId, 'onboarding_reset');
    }

    /**
     * Dismiss onboarding (mark as not interested)
     *
     * @param string $userId
     * @return void
     */
    public function dismissOnboarding(string $userId): void
    {
        DB::table('cmis.user_onboarding_progress')
            ->where('user_id', $userId)
            ->update([
                'is_completed' => true,
                'dismissed' => true,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        Cache::forget("onboarding:progress:{$userId}");

        $this->logOnboardingEvent($userId, 'onboarding_dismissed');
    }

    /**
     * Get onboarding checklist for UI
     *
     * @param string $userId
     * @return array
     */
    public function getChecklist(string $userId): array
    {
        $progress = $this->getProgress($userId);
        $checklist = [];

        foreach ($this->steps as $key => $step) {
            $checklist[] = [
                'key' => $key,
                'title' => __('onboarding.steps.' . $key . '.title', [], app()->getLocale()),
                'description' => __('onboarding.steps.' . $key . '.description', [], app()->getLocale()),
                'icon' => $step['icon'],
                'required' => $step['required'],
                'completed' => in_array($key, $progress['completed_steps']),
                'skipped' => in_array($key, $progress['skipped_steps']),
                'is_current' => $progress['current_step'] === $key,
                'action_url' => $this->getStepActionUrl($key),
            ];
        }

        return $checklist;
    }

    /**
     * Initialize onboarding for new user
     */
    protected function initializeOnboarding(string $userId): void
    {
        DB::table('cmis.user_onboarding_progress')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'completed_steps' => json_encode([]),
            'skipped_steps' => json_encode([]),
            'is_completed' => false,
            'dismissed' => false,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logOnboardingEvent($userId, 'onboarding_started');
    }

    /**
     * Get steps with their completion status
     */
    protected function getStepsWithStatus(array $completed, array $skipped): array
    {
        $steps = [];

        foreach ($this->steps as $key => $step) {
            $steps[$key] = array_merge($step, [
                'completed' => in_array($key, $completed),
                'skipped' => in_array($key, $skipped),
            ]);
        }

        return $steps;
    }

    /**
     * Get action URL for a step
     */
    protected function getStepActionUrl(string $stepKey): ?string
    {
        $urls = [
            'welcome' => route('onboarding.welcome'),
            'profile_setup' => route('profile.edit'),
            'connect_meta' => route('platforms.connect', ['platform' => 'meta']),
            'first_campaign' => route('campaigns.create'),
            'explore_dashboard' => route('dashboard'),
        ];

        return $urls[$stepKey] ?? null;
    }

    /**
     * Log onboarding event
     */
    protected function logOnboardingEvent(string $userId, string $event, array $data = []): void
    {
        try {
            DB::table('cmis.activity_log')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'event_type' => 'onboarding',
                'event_name' => $event,
                'event_data' => json_encode($data),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silent fail - don't break onboarding if logging fails
            \Illuminate\Support\Facades\Log::warning('Failed to log onboarding event', [
                'user_id' => $userId,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Onboarding\UserOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * User Onboarding Controller
 *
 * Handles progressive 5-step user onboarding flow.
 * Part of Phase 1 - User Experience (2025-11-21)
 */
class UserOnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserOnboardingService $onboardingService
    ) {}

    /**
     * Display onboarding dashboard
     */
    public function index(): View
    {
        $user = auth()->user();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $progress = $this->onboardingService->getProgress($user->id);
        $tips = $this->onboardingService->getContextualTips($user->id, $progress['current_step']);

        return view('onboarding.index', [
            'progress' => $progress,
            'tips' => $tips,
            'steps' => $this->getStepDefinitions(),
        ]);
    }

    /**
     * Display specific onboarding step
     */
    public function showStep(int $step): View|RedirectResponse
    {
        $user = auth()->user();

        // Validate step number
        if ($step < 1 || $step > 5) {
            return redirect()->route('onboarding.index');
        }

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $progress = $this->onboardingService->getProgress($user->id);
        $tips = $this->onboardingService->getContextualTips($user->id, $step);
        $stepDefinitions = $this->getStepDefinitions();

        return view('onboarding.step', [
            'step' => $step,
            'progress' => $progress,
            'tips' => $tips,
            'step_definition' => $stepDefinitions[$step - 1] ?? null,
            'all_steps' => $stepDefinitions,
        ]);
    }

    /**
     * Complete an onboarding step
     */
    public function completeStep(Request $request, int $step): JsonResponse
    {
        $user = auth()->user();

        // Validate step data if needed
        $metadata = $request->input('metadata', []);

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $result = $this->onboardingService->completeStep($user->id, $step, $metadata);

        // Get updated progress
        $progress = $this->onboardingService->getProgress($user->id);

        return response()->json([
            'success' => true,
            'message' => __('onboarding.step_completed'),
            'step' => $step,
            'progress' => $progress,
            'next_step' => $progress['current_step'],
        ]);
    }

    /**
     * Skip an onboarding step
     */
    public function skipStep(Request $request, int $step): JsonResponse
    {
        $user = auth()->user();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $result = $this->onboardingService->skipStep($user->id, $step);

        // Get updated progress
        $progress = $this->onboardingService->getProgress($user->id);

        return response()->json([
            'success' => true,
            'message' => __('onboarding.step_skipped'),
            'step' => $step,
            'progress' => $progress,
            'next_step' => $progress['current_step'],
        ]);
    }

    /**
     * Reset onboarding progress (start over)
     */
    public function reset(): RedirectResponse
    {
        $user = auth()->user();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        // Delete existing progress
        DB::table('cmis.user_onboarding_progress')
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('onboarding.index')
            ->with('success', __('onboarding.reset_complete'));
    }

    /**
     * Dismiss onboarding entirely
     */
    public function dismiss(): JsonResponse
    {
        $user = auth()->user();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        // Mark all steps as skipped
        for ($step = 1; $step <= 5; $step++) {
            $this->onboardingService->skipStep($user->id, $step);
        }

        return response()->json([
            'success' => true,
            'message' => __('onboarding.dismissed'),
        ]);
    }

    /**
     * Get onboarding progress (API endpoint)
     */
    public function getProgress(): JsonResponse
    {
        $user = auth()->user();

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $progress = $this->onboardingService->getProgress($user->id);

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Get contextual tips for current step (API endpoint)
     */
    public function getTips(Request $request): JsonResponse
    {
        $user = auth()->user();
        $step = $request->input('step');

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?)", [$user->org_id]);

        $tips = $this->onboardingService->getContextualTips($user->id, $step);

        return response()->json([
            'success' => true,
            'tips' => $tips,
        ]);
    }

    /**
     * Get step definitions
     */
    protected function getStepDefinitions(): array
    {
        return [
            [
                'number' => 1,
                'key' => 'profile_setup',
                'title' => __('onboarding.steps.profile_setup.title'),
                'description' => __('onboarding.steps.profile_setup.description'),
                'icon' => 'user-circle',
                'estimated_time' => '2 min',
                'tasks' => [
                    __('onboarding.steps.profile_setup.tasks.complete_profile'),
                    __('onboarding.steps.profile_setup.tasks.upload_logo'),
                    __('onboarding.steps.profile_setup.tasks.set_preferences'),
                ],
            ],
            [
                'number' => 2,
                'key' => 'platform_connection',
                'title' => __('onboarding.steps.platform_connection.title'),
                'description' => __('onboarding.steps.platform_connection.description'),
                'icon' => 'link',
                'estimated_time' => '5 min',
                'tasks' => [
                    __('onboarding.steps.platform_connection.tasks.connect_meta'),
                    __('onboarding.steps.platform_connection.tasks.authorize_access'),
                    __('onboarding.steps.platform_connection.tasks.sync_accounts'),
                ],
            ],
            [
                'number' => 3,
                'key' => 'first_campaign',
                'title' => __('onboarding.steps.first_campaign.title'),
                'description' => __('onboarding.steps.first_campaign.description'),
                'icon' => 'rocket',
                'estimated_time' => '10 min',
                'tasks' => [
                    __('onboarding.steps.first_campaign.tasks.use_wizard'),
                    __('onboarding.steps.first_campaign.tasks.set_budget'),
                    __('onboarding.steps.first_campaign.tasks.review_launch'),
                ],
            ],
            [
                'number' => 4,
                'key' => 'team_setup',
                'title' => __('onboarding.steps.team_setup.title'),
                'description' => __('onboarding.steps.team_setup.description'),
                'icon' => 'users',
                'estimated_time' => '3 min',
                'tasks' => [
                    __('onboarding.steps.team_setup.tasks.invite_members'),
                    __('onboarding.steps.team_setup.tasks.assign_roles'),
                    __('onboarding.steps.team_setup.tasks.configure_permissions'),
                ],
            ],
            [
                'number' => 5,
                'key' => 'analytics_tour',
                'title' => __('onboarding.steps.analytics_tour.title'),
                'description' => __('onboarding.steps.analytics_tour.description'),
                'icon' => 'chart-bar',
                'estimated_time' => '5 min',
                'tasks' => [
                    __('onboarding.steps.analytics_tour.tasks.explore_dashboard'),
                    __('onboarding.steps.analytics_tour.tasks.understand_metrics'),
                    __('onboarding.steps.analytics_tour.tasks.setup_alerts'),
                ],
            ],
        ];
    }
}

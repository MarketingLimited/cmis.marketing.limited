<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Services\Campaign\CampaignWizardService;
use App\Exceptions\WizardStepException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

/**
 * Campaign Wizard Controller
 *
 * Handles multi-step campaign creation flow with session management.
 * Part of Phase 1B - UX Improvement (2025-11-21)
 */
class CampaignWizardController extends Controller
{
    public function __construct(
        private CampaignWizardService $wizardService
    ) {}

    /**
     * Start new wizard session
     */
    public function create(): RedirectResponse
    {
        $user = auth()->user();

        $session = $this->wizardService->startWizard($user->id, $user->org_id);

        return redirect()->route('campaign.wizard.step', [
            'session_id' => $session['session_id'],
            'step' => 1,
        ])->with('success', __('campaigns.wizard.started'));
    }

    /**
     * Display wizard step
     */
    public function showStep(string $sessionId, int $step): View|RedirectResponse
    {
        try {
            $session = $this->wizardService->getSession($sessionId);

            if (!$session) {
                return redirect()->route('campaigns.index')
                    ->with('error', __('campaigns.wizard.session_expired'));
            }

            // Verify user owns this session
            if ($session['user_id'] !== auth()->id()) {
                abort(403, 'Unauthorized wizard session');
            }

            $steps = $this->wizardService->getSteps();
            $progress = $this->wizardService->getProgress($sessionId);

            // Get step configuration
            $stepConfig = $steps[$step] ?? null;
            if (!$stepConfig) {
                return redirect()->route('campaign.wizard.step', [
                    'session_id' => $sessionId,
                    'step' => 1,
                ]);
            }

            // Load additional data based on step
            $stepData = $this->loadStepData($step, $session);

            return view('campaigns.wizard.step', [
                'session' => $session,
                'session_id' => $sessionId,
                'current_step' => $step,
                'step_config' => $stepConfig,
                'progress' => $progress,
                'all_steps' => $steps,
                'step_data' => $stepData,
            ]);

        } catch (WizardStepException $e) {
            return redirect()->route('campaigns.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update wizard step
     */
    public function updateStep(
        Request $request,
        string $sessionId,
        int $step
    ): RedirectResponse {
        try {
            $session = $this->wizardService->getSession($sessionId);

            if (!$session || $session['user_id'] !== auth()->id()) {
                throw new WizardStepException('Invalid wizard session');
            }

            // Validate step-specific data
            $validated = $this->validateStepData($request, $step);

            // Update step data
            $this->wizardService->updateStep($sessionId, $step, $validated);

            // Handle action (next, previous, save)
            $action = $request->input('action', 'save');

            switch ($action) {
                case 'next':
                    try {
                        $this->wizardService->nextStep($sessionId);
                        return redirect()->route('campaign.wizard.step', [
                            'session_id' => $sessionId,
                            'step' => $step + 1,
                        ])->with('success', __('campaigns.wizard.step_saved'));
                    } catch (WizardStepException $e) {
                        return redirect()->back()
                            ->withErrors(['wizard' => $e->getMessage()])
                            ->withInput();
                    }

                case 'previous':
                    try {
                        $this->wizardService->previousStep($sessionId);
                        return redirect()->route('campaign.wizard.step', [
                            'session_id' => $sessionId,
                            'step' => $step - 1,
                        ]);
                    } catch (WizardStepException $e) {
                        return redirect()->route('campaign.wizard.step', [
                            'session_id' => $sessionId,
                            'step' => 1,
                        ]);
                    }

                case 'save':
                default:
                    return redirect()->back()
                        ->with('success', __('campaigns.wizard.progress_saved'));
            }

        } catch (WizardStepException $e) {
            return redirect()->back()
                ->withErrors(['wizard' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Save campaign as draft
     */
    public function saveDraft(string $sessionId): RedirectResponse
    {
        try {
            $session = $this->wizardService->getSession($sessionId);

            if (!$session || $session['user_id'] !== auth()->id()) {
                throw new WizardStepException('Invalid wizard session');
            }

            // Set RLS context for campaign creation
            DB::statement("SELECT cmis.init_transaction_context(?)", [auth()->user()->org_id]);

            $draft = $this->wizardService->saveDraft($sessionId);

            return redirect()->route('campaigns.show', $draft->id)
                ->with('success', __('campaigns.saved_as_draft'));

        } catch (WizardStepException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Complete wizard and create campaign
     */
    public function complete(string $sessionId): RedirectResponse
    {
        try {
            $session = $this->wizardService->getSession($sessionId);

            if (!$session || $session['user_id'] !== auth()->id()) {
                throw new WizardStepException('Invalid wizard session');
            }

            // Set RLS context for campaign creation
            DB::statement("SELECT cmis.init_transaction_context(?)", [auth()->user()->org_id]);

            $campaign = $this->wizardService->complete($sessionId);

            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', __('campaigns.created_successfully'));

        } catch (WizardStepException $e) {
            return redirect()->back()
                ->withErrors(['wizard' => $e->getMessage()])
                ->with('error', __('campaigns.wizard.completion_error'));
        }
    }

    /**
     * Cancel wizard and return to campaigns
     */
    public function cancel(string $sessionId): RedirectResponse
    {
        $session = $this->wizardService->getSession($sessionId);

        if ($session && $session['user_id'] === auth()->id()) {
            // Clear session
            \Cache::forget("campaign_wizard:session:{$sessionId}");
        }

        return redirect()->route('campaigns.index')
            ->with('info', __('campaigns.wizard.cancelled'));
    }

    /**
     * Load additional data for specific wizard steps
     */
    protected function loadStepData(int $step, array $session): array
    {
        $data = [];

        switch ($step) {
            case 1: // Basics
                $data['objectives'] = [
                    'awareness' => __('campaigns.objectives.awareness'),
                    'traffic' => __('campaigns.objectives.traffic'),
                    'engagement' => __('campaigns.objectives.engagement'),
                    'conversions' => __('campaigns.objectives.conversions'),
                    'app_installs' => __('campaigns.objectives.app_installs'),
                ];
                break;

            case 2: // Targeting
                $data['audience_types'] = [
                    'custom' => __('campaigns.targeting.custom'),
                    'lookalike' => __('campaigns.targeting.lookalike'),
                    'saved' => __('campaigns.targeting.saved'),
                ];

                // Load saved audiences for org
                DB::statement("SELECT cmis.init_transaction_context(?)", [auth()->user()->org_id]);
                $data['saved_audiences'] = DB::table('cmis.audiences')
                    ->select('id', 'name', 'estimated_size')
                    ->where('org_id', auth()->user()->org_id)
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get();
                break;

            case 3: // Creative
                $data['ad_formats'] = [
                    'single_image' => __('campaigns.formats.single_image'),
                    'carousel' => __('campaigns.formats.carousel'),
                    'video' => __('campaigns.formats.video'),
                    'collection' => __('campaigns.formats.collection'),
                ];
                break;

            case 4: // Review
                // Include all data for review
                $data['full_data'] = $session['data'];
                break;
        }

        return $data;
    }

    /**
     * Validate step-specific data
     */
    protected function validateStepData(Request $request, int $step): array
    {
        $rules = [];

        switch ($step) {
            case 1: // Basics
                $rules = [
                    'name' => ['required', 'string', 'max:255'],
                    'objective' => ['required', 'string', 'in:awareness,traffic,engagement,conversions,app_installs'],
                    'budget_total' => ['required', 'numeric', 'min:10', 'max:1000000'],
                    'start_date' => ['required', 'date', 'after_or_equal:today'],
                    'end_date' => ['nullable', 'date', 'after:start_date'],
                    'description' => ['nullable', 'string', 'max:1000'],
                ];
                break;

            case 2: // Targeting
                $rules = [
                    'audience_type' => ['required', 'string', 'in:custom,lookalike,saved'],
                    'saved_audience_id' => ['required_if:audience_type,saved', 'nullable', 'uuid', 'exists:cmis.audiences,id'],
                    'age_min' => ['nullable', 'integer', 'min:18', 'max:65'],
                    'age_max' => ['nullable', 'integer', 'min:18', 'max:65', 'gte:age_min'],
                    'genders' => ['nullable', 'array'],
                    'locations' => ['nullable', 'array'],
                    'interests' => ['nullable', 'array'],
                ];
                break;

            case 3: // Creative
                $rules = [
                    'ad_format' => ['required', 'string', 'in:single_image,carousel,video,collection'],
                    'primary_text' => ['required', 'string', 'max:500'],
                    'headline' => ['nullable', 'string', 'max:100'],
                    'description' => ['nullable', 'string', 'max:200'],
                    'call_to_action' => ['nullable', 'string', 'max:50'],
                    'media_urls' => ['nullable', 'array'],
                    'media_urls.*' => ['url'],
                ];
                break;

            case 4: // Review
                // No additional validation needed for review step
                break;
        }

        return $request->validate($rules);
    }
}

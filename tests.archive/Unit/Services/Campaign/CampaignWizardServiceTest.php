<?php

namespace Tests\Unit\Services\Campaign;

use Tests\TestCase;
use Tests\TestHelpers\DatabaseHelpers;
use App\Services\Campaign\CampaignWizardService;
use App\Exceptions\WizardStepException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Campaign Wizard Service Test
 *
 * Tests multi-step campaign creation workflow with session management.
 * Part of Phase 2 - UX improvements (2025-11-21)
 */
class CampaignWizardServiceTest extends TestCase
{
    use RefreshDatabase, DatabaseHelpers;

    protected CampaignWizardService $wizardService;
    protected object $org;
    protected object $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wizardService = new CampaignWizardService();

        // Create test organization and user
        $this->org = $this->createTestOrg();
        $this->user = $this->createTestUser($this->org->id);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestOrg($this->org->id);
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_starts_new_wizard_session()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);

        $this->assertIsArray($session);
        $this->assertArrayHasKey('session_id', $session);
        $this->assertArrayHasKey('user_id', $session);
        $this->assertArrayHasKey('org_id', $session);
        $this->assertEquals(1, $session['current_step']);
        $this->assertEmpty($session['completed_steps']);
        $this->assertEmpty($session['data']);
    }

    /** @test */
    public function it_retrieves_wizard_session_from_cache()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        $retrieved = $this->wizardService->getSession($sessionId);

        $this->assertNotNull($retrieved);
        $this->assertEquals($sessionId, $retrieved['session_id']);
        $this->assertEquals($this->user->id, $retrieved['user_id']);
    }

    /** @test */
    public function it_updates_step_data()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        $stepData = [
            'name' => 'Test Campaign',
            'objective' => 'awareness',
            'budget_total' => 1000,
            'start_date' => now()->toDateString(),
        ];

        $updated = $this->wizardService->updateStep($sessionId, 1, $stepData);

        $this->assertEquals('Test Campaign', $updated['data']['name']);
        $this->assertEquals('awareness', $updated['data']['objective']);
        $this->assertContains(1, $updated['completed_steps']);
    }

    /** @test */
    public function it_marks_step_as_complete_when_all_required_fields_present()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Step 1 requires: name, objective, budget_total, start_date
        $completeData = [
            'name' => 'Complete Campaign',
            'objective' => 'conversions',
            'budget_total' => 2000,
            'start_date' => now()->addDay()->toDateString(),
        ];

        $updated = $this->wizardService->updateStep($sessionId, 1, $completeData);

        $this->assertContains(1, $updated['completed_steps']);
    }

    /** @test */
    public function it_does_not_mark_step_complete_if_required_fields_missing()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Missing 'start_date' required field
        $incompleteData = [
            'name' => 'Incomplete Campaign',
            'objective' => 'engagement',
            'budget_total' => 1500,
        ];

        $updated = $this->wizardService->updateStep($sessionId, 1, $incompleteData);

        $this->assertNotContains(1, $updated['completed_steps']);
    }

    /** @test */
    public function it_merges_step_data_on_multiple_updates()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // First update
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Campaign Name',
            'objective' => 'awareness',
        ]);

        // Second update
        $updated = $this->wizardService->updateStep($sessionId, 1, [
            'budget_total' => 3000,
            'start_date' => now()->toDateString(),
        ]);

        $this->assertEquals('Campaign Name', $updated['data']['name']);
        $this->assertEquals(3000, $updated['data']['budget_total']);
    }

    /** @test */
    public function it_moves_to_next_step_when_current_step_complete()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Complete step 1
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Test',
            'objective' => 'awareness',
            'budget_total' => 1000,
            'start_date' => now()->toDateString(),
        ]);

        $nextSession = $this->wizardService->nextStep($sessionId);

        $this->assertEquals(2, $nextSession['current_step']);
    }

    /** @test */
    public function it_throws_exception_when_moving_to_next_step_without_completing_current()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Don't complete step 1
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Incomplete',
        ]);

        $this->expectException(WizardStepException::class);
        $this->expectExceptionMessage('Please complete all required fields');

        $this->wizardService->nextStep($sessionId);
    }

    /** @test */
    public function it_moves_to_previous_step()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Complete step 1 and move to step 2
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Test',
            'objective' => 'awareness',
            'budget_total' => 1000,
            'start_date' => now()->toDateString(),
        ]);
        $this->wizardService->nextStep($sessionId);

        // Go back to step 1
        $previousSession = $this->wizardService->previousStep($sessionId);

        $this->assertEquals(1, $previousSession['current_step']);
    }

    /** @test */
    public function it_throws_exception_when_going_back_from_first_step()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        $this->expectException(WizardStepException::class);
        $this->expectExceptionMessage('Already at first step');

        $this->wizardService->previousStep($sessionId);
    }

    /** @test */
    public function it_saves_campaign_as_draft()
    {
        $this->setRLSContext($this->org->id);

        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Add some data
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Draft Campaign',
            'objective' => 'traffic',
            'budget_total' => 500,
        ]);

        $draft = $this->wizardService->saveDraft($sessionId);

        $this->assertNotNull($draft);
        $this->assertEquals('draft', $draft->status);
        $this->assertEquals('Draft Campaign', $draft->name);
        $this->assertEquals($this->org->id, $draft->org_id);

        // Verify draft in database
        $this->setRLSContext($this->org->id);
        $dbDraft = DB::table('cmis.campaigns')
            ->where('id', $draft->id)
            ->first();

        $this->assertNotNull($dbDraft);
        $this->assertEquals($sessionId, $dbDraft->wizard_session_id);
    }

    /** @test */
    public function it_completes_wizard_and_creates_campaign()
    {
        $this->setRLSContext($this->org->id);

        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Complete all steps
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Complete Campaign',
            'objective' => 'conversions',
            'budget_total' => 5000,
            'start_date' => now()->toDateString(),
        ]);

        $this->wizardService->nextStep($sessionId);

        $this->wizardService->updateStep($sessionId, 2, [
            'audience_type' => 'custom',
        ]);

        $this->wizardService->nextStep($sessionId);

        $this->wizardService->updateStep($sessionId, 3, [
            'ad_format' => 'single_image',
            'primary_text' => 'Test ad copy',
        ]);

        $this->wizardService->nextStep($sessionId);

        // Complete the wizard
        $campaign = $this->wizardService->complete($sessionId);

        $this->assertNotNull($campaign);
        $this->assertEquals('Complete Campaign', $campaign->name);
        $this->assertEquals('pending_review', $campaign->status);
        $this->assertEquals($this->org->id, $campaign->org_id);

        // Verify session cleared
        $clearedSession = $this->wizardService->getSession($sessionId);
        $this->assertNull($clearedSession);
    }

    /** @test */
    public function it_throws_exception_when_completing_with_incomplete_steps()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Only complete step 1
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Incomplete',
            'objective' => 'awareness',
            'budget_total' => 1000,
            'start_date' => now()->toDateString(),
        ]);

        $this->expectException(WizardStepException::class);
        $this->expectExceptionMessage('is not complete');

        $this->wizardService->complete($sessionId);
    }

    /** @test */
    public function it_calculates_progress_percentage()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // No steps completed
        $progress = $this->wizardService->getProgress($sessionId);
        $this->assertEquals(0, $progress);

        // Complete step 1 (1 of 4 = 25%)
        $this->wizardService->updateStep($sessionId, 1, [
            'name' => 'Test',
            'objective' => 'awareness',
            'budget_total' => 1000,
            'start_date' => now()->toDateString(),
        ]);

        $progress = $this->wizardService->getProgress($sessionId);
        $this->assertEquals(25, $progress);

        // Complete step 2 (2 of 4 = 50%)
        $this->wizardService->updateStep($sessionId, 2, [
            'audience_type' => 'custom',
        ]);

        $progress = $this->wizardService->getProgress($sessionId);
        $this->assertEquals(50, $progress);
    }

    /** @test */
    public function it_returns_wizard_steps_configuration()
    {
        $steps = $this->wizardService->getSteps();

        $this->assertIsArray($steps);
        $this->assertCount(4, $steps);

        $this->assertEquals('basics', $steps[1]['key']);
        $this->assertEquals('targeting', $steps[2]['key']);
        $this->assertEquals('creative', $steps[3]['key']);
        $this->assertEquals('review', $steps[4]['key']);

        // Check required fields
        $this->assertContains('name', $steps[1]['required_fields']);
        $this->assertContains('audience_type', $steps[2]['required_fields']);
        $this->assertContains('ad_format', $steps[3]['required_fields']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_step_number()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        $this->expectException(WizardStepException::class);
        $this->expectExceptionMessage('Invalid wizard step');

        $this->wizardService->updateStep($sessionId, 99, ['invalid' => 'data']);
    }

    /** @test */
    public function it_throws_exception_for_expired_session()
    {
        $this->expectException(WizardStepException::class);
        $this->expectExceptionMessage('Wizard session not found');

        $this->wizardService->getSession('non-existent-session-id');
        $this->wizardService->nextStep('non-existent-session-id');
    }

    /** @test */
    public function wizard_session_expires_after_ttl()
    {
        $session = $this->wizardService->startWizard($this->user->id, $this->org->id);
        $sessionId = $session['session_id'];

        // Manually expire the cache (simulating TTL expiration)
        Cache::forget("campaign_wizard:session:{$sessionId}");

        $retrieved = $this->wizardService->getSession($sessionId);

        $this->assertNull($retrieved);
    }
}

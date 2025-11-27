<?php

namespace Tests\Feature\ABTesting;

use App\Models\ABTesting\ABTest;
use App\Models\ABTesting\ABTestVariant;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ABTestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        session(['current_org_id' => $this->org->org_id]);
    }

    /** @test */
    public function it_can_list_ab_tests()
    {
        ABTest::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.tests.index'));

        $response->assertOk();
        $response->assertViewIs('ab-testing.tests.index');
        $response->assertViewHas('tests');
    }

    /** @test */
    public function it_can_create_an_ab_test()
    {
        $data = [
            'test_name' => 'Homepage Test',
            'entity_type' => 'campaign',
            'entity_id' => '11111111-1111-1111-1111-111111111111',
            'status' => 'draft',
            'sample_size' => 1000,
            'confidence_level' => 95,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('ab-testing.tests.store'), $data);

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'org_id' => $this->org->org_id,
            'test_name' => 'Homepage Test',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_show_an_ab_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        ABTestVariant::factory()->count(2)->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.tests.show', $test->test_id));

        $response->assertOk();
        $response->assertViewIs('ab-testing.tests.show');
        $response->assertViewHas('test');
    }

    /** @test */
    public function it_can_update_an_ab_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'test_name' => 'Original Name',
        ]);

        $data = [
            'test_name' => 'Updated Name',
            'sample_size' => 2000,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('ab-testing.tests.update', $test->test_id), $data);

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
            'test_name' => 'Updated Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_an_ab_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('ab-testing.tests.destroy', $test->test_id));

        $this->assertSoftDeleted('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_start_a_draft_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        ABTestVariant::factory()->count(2)->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.start', $test->test_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
            'status' => 'running',
        ]);
    }

    /** @test */
    public function it_cannot_start_test_with_less_than_two_variants()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        ABTestVariant::factory()->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.start', $test->test_id));

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_pause_a_running_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.pause', $test->test_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
            'status' => 'paused',
        ]);
    }

    /** @test */
    public function it_can_resume_a_paused_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'paused',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.resume', $test->test_id));

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
            'status' => 'running',
        ]);
    }

    /** @test */
    public function it_can_complete_a_test()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        $variant = ABTestVariant::factory()->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.complete', $test->test_id), [
                'winner_variant_id' => $variant->variant_id,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
            'test_id' => $test->test_id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_can_get_test_results()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        ABTestVariant::factory()->count(2)->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ab-testing.tests.results', $test->test_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'test_id',
                'test_name',
                'status',
                'variants',
            ],
        ]);
    }

    /** @test */
    public function it_can_calculate_statistical_significance()
    {
        $test = ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        ABTestVariant::factory()->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => true,
            'impressions' => 1000,
            'conversions' => 50,
        ]);

        ABTestVariant::factory()->create([
            'test_id' => $test->test_id,
            'org_id' => $this->org->org_id,
            'is_control' => false,
            'impressions' => 1000,
            'conversions' => 65,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ab-testing.tests.calculateSignificance', $test->test_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'variant_id',
                    'z_score',
                    'p_value',
                    'is_significant',
                ],
            ],
        ]);
    }

    /** @test */
    public function it_can_get_ab_testing_analytics()
    {
        ABTest::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('ab-testing.tests.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'by_entity_type',
                'by_status',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_update_tests()
    {
        $tests = ABTest::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('ab-testing.tests.bulkUpdate'), [
                'test_ids' => $tests->pluck('test_id')->toArray(),
                'status' => 'cancelled',
            ]);

        $response->assertOk();

        foreach ($tests as $test) {
            $this->assertDatabaseHas('cmis_ab_testing.ab_tests', [
                'test_id' => $test->test_id,
                'status' => 'cancelled',
            ]);
        }
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        $otherTest = ABTest::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.tests.show', $otherTest->test_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_filters_tests_by_status()
    {
        ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'running',
        ]);

        ABTest::factory()->create([
            'org_id' => $this->org->org_id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('ab-testing.tests.index', ['status' => 'running']));

        $response->assertOk();
    }
}

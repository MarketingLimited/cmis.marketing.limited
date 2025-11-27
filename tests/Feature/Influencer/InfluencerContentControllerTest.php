<?php

namespace Tests\Feature\Influencer;

use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerCampaign;
use App\Models\Influencer\InfluencerContent;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InfluencerContentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected Influencer $influencer;
    protected InfluencerCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create();

        $this->user->orgs()->attach($this->org->org_id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        session(['current_org_id' => $this->org->org_id]);

        $this->influencer = Influencer::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $this->campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);
    }

    /** @test */
    public function it_can_list_content()
    {
        InfluencerContent::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.content.index'));

        $response->assertOk();
        $response->assertViewIs('influencer.content.index');
        $response->assertViewHas('content');
    }

    /** @test */
    public function it_can_create_content()
    {
        $data = [
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'title' => 'Test Content',
            'content_type' => 'post',
            'platform' => 'instagram',
            'approval_status' => 'pending',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('influencer.content.store'), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_content', [
            'org_id' => $this->org->org_id,
            'title' => 'Test Content',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_show_content()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.content.show', $content->content_id));

        $response->assertOk();
        $response->assertViewIs('influencer.content.show');
        $response->assertViewHas('content');
    }

    /** @test */
    public function it_can_update_content()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'title' => 'Original Title',
        ]);

        $data = [
            'title' => 'Updated Title',
            'content_type' => 'story',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('influencer.content.update', $content->content_id), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_content', [
            'content_id' => $content->content_id,
            'title' => 'Updated Title',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_content()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('influencer.content.destroy', $content->content_id));

        $this->assertSoftDeleted('cmis_influencer.influencer_content', [
            'content_id' => $content->content_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_update_approval_status()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.updateApproval', $content->content_id), [
                'approval_status' => 'approved',
                'approval_feedback' => 'Looks great!',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_content', [
            'content_id' => $content->content_id,
            'approval_status' => 'approved',
        ]);
    }

    /** @test */
    public function it_can_upload_media()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.uploadMedia', $content->content_id), [
                'media' => $file,
                'media_type' => 'image',
            ]);

        $response->assertOk();

        Storage::disk('public')->assertExists('influencer-content/' . $content->content_id . '/' . $file->hashName());
    }

    /** @test */
    public function it_can_get_content_performance()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.content.performance', $content->content_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'content_id',
                'total_reach',
                'total_engagement',
                'engagement_rate',
            ],
        ]);
    }

    /** @test */
    public function it_can_publish_content()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'approval_status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.publish', $content->content_id), [
                'scheduled_at' => now()->addHours(2)->toIso8601String(),
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_content', [
            'content_id' => $content->content_id,
        ]);
    }

    /** @test */
    public function it_cannot_publish_unapproved_content()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.publish', $content->content_id));

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_get_content_analytics()
    {
        InfluencerContent::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.content.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'by_type',
                'by_platform',
                'by_status',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_update_content()
    {
        $contents = InfluencerContent::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.bulkUpdate'), [
                'content_ids' => $contents->pluck('content_id')->toArray(),
                'approval_status' => 'approved',
            ]);

        $response->assertOk();

        foreach ($contents as $content) {
            $this->assertDatabaseHas('cmis_influencer.influencer_content', [
                'content_id' => $content->content_id,
                'approval_status' => 'approved',
            ]);
        }
    }

    /** @test */
    public function it_can_get_top_performing_content()
    {
        InfluencerContent::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.content.topPerforming', ['limit' => 5]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_request_content_revision()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'approval_status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.content.requestRevision', $content->content_id), [
                'revision_notes' => 'Please adjust the tone',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_content', [
            'content_id' => $content->content_id,
            'approval_status' => 'revision_requested',
        ]);
    }

    /** @test */
    public function it_can_export_content_report()
    {
        $content = InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.content.export', $content->content_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'content',
                'campaign',
                'influencer',
                'performance',
                'exported_at',
            ],
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();
        $otherInfluencer = Influencer::factory()->create([
            'org_id' => $otherOrg->org_id,
        ]);
        $otherCampaign = InfluencerCampaign::factory()->create([
            'org_id' => $otherOrg->org_id,
            'influencer_id' => $otherInfluencer->influencer_id,
        ]);

        $otherContent = InfluencerContent::factory()->create([
            'org_id' => $otherOrg->org_id,
            'campaign_id' => $otherCampaign->campaign_id,
            'influencer_id' => $otherInfluencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.content.show', $otherContent->content_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_filters_content_by_campaign()
    {
        $campaign2 = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        InfluencerContent::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $campaign2->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.content.index', ['campaign_id' => $this->campaign->campaign_id]));

        $response->assertOk();
    }
}

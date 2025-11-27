<?php

namespace Tests\Feature\Social;

use App\Models\Social\SocialMention;
use App\Models\Social\SocialTrend;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialListeningControllerTest extends TestCase
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
    public function it_can_list_social_mentions()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('social.listening.index'));

        $response->assertOk();
        $response->assertViewIs('social.listening.index');
        $response->assertViewHas('mentions');
    }

    /** @test */
    public function it_can_get_sentiment_summary()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'sentiment' => 'positive',
        ]);

        SocialMention::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'sentiment' => 'negative',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.sentimentSummary'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_mentions',
                'positive_mentions',
                'negative_mentions',
                'neutral_mentions',
                'avg_sentiment_score',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_trending_topics()
    {
        SocialTrend::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.trendingTopics'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_mention_volume_over_time()
    {
        SocialMention::factory()->count(20)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.mentionVolume', [
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
                'interval' => 'day',
            ]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_top_influencers()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'author_followers' => 10000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.topInfluencers'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_platform_distribution()
    {
        SocialMention::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'platform' => 'twitter',
        ]);

        SocialMention::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.platformDistribution'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_keyword_analysis()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'content' => 'This is great product amazing',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.keywordAnalysis'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_engagement_metrics()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'engagement' => 100,
            'reach' => 1000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.engagementMetrics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'total_engagement',
                'total_reach',
                'avg_engagement_per_mention',
                'engagement_rate',
            ],
        ]);
    }

    /** @test */
    public function it_can_export_listening_report()
    {
        SocialMention::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.exportReport', [
                'format' => 'json',
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'period',
                'sentiment_summary',
                'platform_breakdown',
                'top_mentions',
                'exported_at',
            ],
        ]);
    }

    /** @test */
    public function it_enforces_multi_tenancy()
    {
        $otherOrg = Org::factory()->create();

        SocialMention::factory()->count(5)->create([
            'org_id' => $otherOrg->org_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('social.listening.sentimentSummary'));

        $response->assertOk();
        $response->assertJsonPath('data.total_mentions', 0);
    }

    /** @test */
    public function it_filters_mentions_by_platform()
    {
        SocialMention::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'twitter',
        ]);

        SocialMention::factory()->create([
            'org_id' => $this->org->org_id,
            'platform' => 'facebook',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('social.listening.index', ['platform' => 'twitter']));

        $response->assertOk();
    }

    /** @test */
    public function it_filters_mentions_by_sentiment()
    {
        SocialMention::factory()->create([
            'org_id' => $this->org->org_id,
            'sentiment' => 'positive',
        ]);

        SocialMention::factory()->create([
            'org_id' => $this->org->org_id,
            'sentiment' => 'negative',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('social.listening.index', ['sentiment' => 'positive']));

        $response->assertOk();
    }
}

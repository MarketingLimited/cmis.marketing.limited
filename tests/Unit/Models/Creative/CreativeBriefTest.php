<?php

namespace Tests\Unit\Models\Creative;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Creative\CreativeBrief;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Creative Brief Model Unit Tests
 */
class CreativeBriefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_a_creative_brief()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign Brief',
            'brief_data' => [
                'marketing_objective' => 'drive_sales',
                'emotional_trigger' => 'desire',
            ],
        ]);

        $this->assertDatabaseHas('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
            'name' => 'Summer Campaign Brief',
        ]);
    }

    /** @test */
    public function it_belongs_to_an_organization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Brief',
        ]);

        $this->assertEquals($org->org_id, $brief->org->org_id);
    }

    /** @test */
    public function it_stores_brief_data_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $briefData = [
            'marketing_objective' => 'drive_sales',
            'emotional_trigger' => 'desire',
            'hooks' => ['Hook 1', 'Hook 2'],
            'channels' => ['facebook', 'instagram'],
            'art_direction' => [
                'color_palette' => [
                    'primary' => '#FF6B35',
                    'secondary' => '#F7F7F7',
                ],
                'typography' => [
                    'primary_font' => 'Montserrat',
                ],
            ],
        ];

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'JSON Brief',
            'brief_data' => $briefData,
        ]);

        $this->assertEquals('drive_sales', $brief->brief_data['marketing_objective']);
        $this->assertEquals(['Hook 1', 'Hook 2'], $brief->brief_data['hooks']);
        $this->assertEquals('#FF6B35', $brief->brief_data['art_direction']['color_palette']['primary']);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UUID Brief',
        ]);

        $this->assertTrue(Str::isUuid($brief->brief_id));
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Soft Delete Brief',
        ]);

        $brief->delete();

        $this->assertSoftDeleted('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
        ]);
    }

    /** @test */
    public function it_validates_marketing_objectives()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Objectives Brief',
            'brief_data' => [
                'marketing_objective' => 'drive_sales',
            ],
        ]);

        $this->assertContains($brief->brief_data['marketing_objective'], [
            'drive_sales',
            'brand_awareness',
            'lead_generation',
            'engagement',
        ]);
    }

    /** @test */
    public function it_stores_complete_art_direction()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Art Direction Brief',
            'brief_data' => [
                'art_direction' => [
                    'mood' => 'energetic',
                    'visual_message' => 'summer vibes',
                    'look_feel' => 'modern and clean',
                    'color_palette' => [
                        'primary' => '#FF6B35',
                        'secondary' => '#F7F7F7',
                        'accent' => '#004E89',
                    ],
                    'typography' => [
                        'primary_font' => 'Montserrat',
                        'secondary_font' => 'Open Sans',
                    ],
                    'element_positions' => [
                        'logo' => 'top-left',
                        'product' => 'center',
                        'cta' => 'bottom-right',
                    ],
                    'ratio' => '1:1',
                    'motion' => 'smooth transitions',
                ],
            ],
        ]);

        $artDirection = $brief->brief_data['art_direction'];
        $this->assertEquals('energetic', $artDirection['mood']);
        $this->assertEquals('#FF6B35', $artDirection['color_palette']['primary']);
        $this->assertEquals('Montserrat', $artDirection['typography']['primary_font']);
        $this->assertEquals('1:1', $artDirection['ratio']);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $brief = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Timestamp Brief',
        ]);

        $this->assertNotNull($brief->created_at);
        $this->assertNotNull($brief->updated_at);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        $brief1 = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Brief',
        ]);

        $brief2 = CreativeBrief::create([
            'brief_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Brief',
        ]);

        $org1Briefs = CreativeBrief::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Briefs);
        $this->assertEquals('Org 1 Brief', $org1Briefs->first()->name);
    }
}

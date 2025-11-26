<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Creative Brief API Feature Tests
 */
class CreativeBriefAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_list_creative_briefs_for_organization()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create briefs
        $brief1 = $this->createTestCreativeBrief($org->org_id, ['name' => 'Brief 1']);
        $brief2 = $this->createTestCreativeBrief($org->org_id, ['name' => 'Brief 2']);

        $response = $this->getJson("/api/creative-briefs?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonFragment(['name' => 'Brief 1'])
                 ->assertJsonFragment(['name' => 'Brief 2']);
    }

    #[Test]
    public function it_can_create_a_creative_brief()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $briefData = [
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign 2024',
            'brief_data' => [
                'marketing_objective' => 'drive_sales',
                'emotional_trigger' => 'desire',
                'hooks' => ['Limited offer!', 'Summer sale!'],
                'channels' => ['facebook', 'instagram'],
                'art_direction' => [
                    'color_palette' => [
                        'primary' => '#FF6B35',
                        'secondary' => '#F7F7F7',
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/creative-briefs', $briefData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Summer Campaign 2024']);

        $this->assertDatabaseHas('cmis.creative_briefs', [
            'org_id' => $org->org_id,
            'name' => 'Summer Campaign 2024',
        ]);
    }

    #[Test]
    public function it_can_view_a_specific_creative_brief()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Test Brief',
            'brief_data' => [
                'marketing_objective' => 'brand_awareness',
            ],
        ]);

        $response = $this->getJson("/api/creative-briefs/{$brief->brief_id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Brief'])
                 ->assertJsonPath('data.brief_data.marketing_objective', 'brand_awareness');
    }

    #[Test]
    public function it_can_update_a_creative_brief()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'brief_data' => [
                'marketing_objective' => 'lead_generation',
                'emotional_trigger' => 'fear_of_missing_out',
            ],
        ];

        $response = $this->putJson("/api/creative-briefs/{$brief->brief_id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function it_can_delete_a_creative_brief()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'To Delete',
        ]);

        $response = $this->deleteJson("/api/creative-briefs/{$brief->brief_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
        ]);
    }

    #[Test]
    public function it_enforces_org_isolation_for_briefs()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];
        $user1 = $setup1['user'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

        $brief1 = $this->createTestCreativeBrief($org1->org_id, ['name' => 'Org 1 Brief']);
        $brief2 = $this->createTestCreativeBrief($org2->org_id, ['name' => 'Org 2 Brief']);

        $this->actingAsUserInOrg($user1, $org1);

        $response = $this->getJson("/api/creative-briefs?org_id={$org1->org_id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Org 1 Brief'])
                 ->assertJsonMissing(['name' => 'Org 2 Brief']);
    }

    #[Test]
    public function it_validates_creative_brief_creation_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $invalidData = [
            'org_id' => $org->org_id,
            // Missing required 'name' field
        ];

        $response = $this->postJson('/api/creative-briefs', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/creative-briefs');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_search_creative_briefs()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $this->createTestCreativeBrief($org->org_id, ['name' => 'Summer Campaign']);
        $this->createTestCreativeBrief($org->org_id, ['name' => 'Winter Campaign']);

        $response = $this->getJson("/api/creative-briefs?org_id={$org->org_id}&search=Summer");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Summer Campaign'])
                 ->assertJsonMissing(['name' => 'Winter Campaign']);
    }

    #[Test]
    public function it_includes_complete_art_direction_in_response()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Art Direction Brief',
            'brief_data' => [
                'art_direction' => [
                    'mood' => 'energetic',
                    'color_palette' => [
                        'primary' => '#FF6B35',
                        'secondary' => '#F7F7F7',
                    ],
                    'typography' => [
                        'primary_font' => 'Montserrat',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson("/api/creative-briefs/{$brief->brief_id}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.brief_data.art_direction.mood', 'energetic')
                 ->assertJsonPath('data.brief_data.art_direction.color_palette.primary', '#FF6B35');
    }
}

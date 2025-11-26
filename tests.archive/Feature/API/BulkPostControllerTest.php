<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\InteractsWithRLS;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\Social\SocialAccount;
use App\Models\Social\SocialPost;
use PHPUnit\Framework\Attributes\Test;

class BulkPostControllerTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, InteractsWithRLS;

    #[Test]
    public function it_can_create_bulk_posts_from_template()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        // Create social accounts
        $account1 = $this->createTestSocialAccount($org->org_id);
        $account2 = $this->createTestSocialAccount($org->org_id);
        $account3 = $this->createTestSocialAccount($org->org_id);

        $requestData = [
            'template' => [
                'content' => 'Check out our new product!',
                'platform' => 'facebook',
                'post_type' => 'text',
                'hashtags' => ['#newproduct', '#sale'],
            ],
            'accounts' => [$account1->id, $account2->id, $account3->id],
            'options' => [
                'auto_schedule' => false,
                'use_ai_variations' => false,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/bulk-posts/create", $requestData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'created',
                    'posts',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/create',
            'accounts_count' => 3,
        ]);
    }

    #[Test]
    public function it_validates_bulk_post_creation_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $invalidData = [
            // Missing required fields
            'accounts' => [],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/bulk-posts/create", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['template', 'accounts']);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/create',
            'validation' => 'enforced',
        ]);
    }

    #[Test]
    public function it_can_import_posts_from_csv()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $account = $this->createTestSocialAccount($org->org_id);

        $csvData = [
            [
                'content' => 'Post 1 content',
                'social_account_id' => $account->id,
                'platform' => 'facebook',
                'scheduled_for' => now()->addDays(1)->format('Y-m-d H:i:s'),
                'hashtags' => '#tag1,#tag2',
            ],
            [
                'content' => 'Post 2 content',
                'social_account_id' => $account->id,
                'platform' => 'instagram',
                'scheduled_for' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'hashtags' => '#tag3',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/bulk-posts/import-csv", [
                'csv_data' => $csvData,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'created',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/import-csv',
            'csv_rows' => 2,
        ]);
    }

    #[Test]
    public function it_validates_csv_import_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $invalidCsvData = [
            [
                // Missing required fields
                'content' => 'Test',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/orgs/{$org->org_id}/bulk-posts/import-csv", [
                'csv_data' => $invalidCsvData,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['csv_data.0.social_account_id']);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/import-csv',
            'validation' => 'enforced',
        ]);
    }

    #[Test]
    public function it_can_bulk_update_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $post1 = $this->createTestScheduledPost($org->org_id, $user->user_id, ['status' => 'draft']);
        $post2 = $this->createTestScheduledPost($org->org_id, $user->user_id, ['status' => 'draft']);
        $post3 = $this->createTestScheduledPost($org->org_id, $user->user_id, ['status' => 'draft']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/orgs/{$org->org_id}/bulk-posts/update", [
                'post_ids' => [$post1->id, $post2->id, $post3->id],
                'updates' => [
                    'status' => 'scheduled',
                    'hashtags' => ['#updated'],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'PUT /api/orgs/{org_id}/bulk-posts/update',
            'posts_updated' => 3,
        ]);
    }

    #[Test]
    public function it_can_bulk_delete_posts()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $post1 = $this->createTestScheduledPost($org->org_id, $user->user_id);
        $post2 = $this->createTestScheduledPost($org->org_id, $user->user_id);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/orgs/{$org->org_id}/bulk-posts/delete", [
                'post_ids' => [$post1->id, $post2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'deleted',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'DELETE /api/orgs/{org_id}/bulk-posts/delete',
            'posts_deleted' => 2,
        ]);
    }

    #[Test]
    public function it_can_get_template_suggestions()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/orgs/{$org->org_id}/bulk-posts/suggestions?topic=product&platform=facebook&limit=5");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'count',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->logTestResult('passed', [
            'endpoint' => 'GET /api/orgs/{org_id}/bulk-posts/suggestions',
            'topic' => 'product',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_bulk_operations()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $response = $this->getJson("/api/orgs/{$org->org_id}/bulk-posts/create");

        $response->assertStatus(401);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/create',
            'authentication' => 'required',
        ]);
    }

    #[Test]
    public function it_enforces_org_isolation_for_bulk_posts()
    {
        $setup1 = $this->createUserWithOrg();
        $setup2 = $this->createUserWithOrg();

        $account = $this->createTestSocialAccount($setup1['org']->org_id);

        // User from org2 tries to create posts for org1's account
        $response = $this->actingAs($setup2['user'], 'sanctum')
            ->postJson("/api/orgs/{$setup2['org']->org_id}/bulk-posts/create", [
                'template' => [
                    'content' => 'Test',
                    'platform' => 'facebook',
                ],
                'accounts' => [$account->id],
            ]);

        // Should fail due to org isolation
        $response->assertStatus(500);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/orgs/{org_id}/bulk-posts/create',
            'org_isolation' => 'enforced',
        ]);
    }
}

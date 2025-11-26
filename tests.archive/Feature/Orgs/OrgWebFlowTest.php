<?php

namespace Tests\Feature\Orgs;

use Tests\TestCase;
use App\Models\User;
use App\Models\Core\{Org, UserOrg, Role};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Comprehensive test suite for Organization web flows
 * Tests the complete organization lifecycle through web interface
 */
class OrgWebFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Role $ownerRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Create owner role
        $this->ownerRole = Role::firstOrCreate(
            ['role_code' => 'owner'],
            [
                'role_name' => 'Owner',
                'description' => 'Organization owner with full permissions',
                'is_system' => true,
                'is_active' => true,
            ]
        );
    }

    #[Test]
    public function authenticated_user_can_view_orgs_list_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('orgs.index'));

        $response->assertStatus(200)
            ->assertViewIs('orgs.index')
            ->assertViewHas('orgs');
    }

    #[Test]
    public function unauthenticated_user_cannot_view_orgs_list()
    {
        $response = $this->get(route('orgs.index'));

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_view_create_org_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('orgs.create'));

        $response->assertStatus(200)
            ->assertViewIs('orgs.create');
    }

    #[Test]
    public function user_can_create_new_organization_with_valid_data()
    {
        $orgData = [
            'name' => 'Test Organization ' . Str::random(8),
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
            'provider' => 'manual',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        // Should redirect to org show page
        $response->assertStatus(302)
            ->assertSessionHas('success');

        // Verify org was created in database
        $this->assertDatabaseHas('cmis.orgs', [
            'name' => $orgData['name'],
            'default_locale' => $orgData['default_locale'],
            'currency' => $orgData['currency'],
            'provider' => $orgData['provider'],
        ]);

        // Verify user was attached as owner
        $org = Org::where('name', $orgData['name'])->first();
        $this->assertNotNull($org);

        $this->assertDatabaseHas('cmis.user_orgs', [
            'user_id' => $this->user->user_id,
            'org_id' => $org->org_id,
            'role_id' => $this->ownerRole->role_id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function user_can_create_organization_with_minimal_required_data()
    {
        $orgData = [
            'name' => 'Minimal Org ' . Str::random(8),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        // Verify defaults are applied
        $this->assertDatabaseHas('cmis.orgs', [
            'name' => $orgData['name'],
            'default_locale' => 'ar-BH',  // Default value
            'currency' => 'BHD',          // Default value
        ]);
    }

    #[Test]
    public function organization_creation_fails_without_required_name()
    {
        $orgData = [
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name']);

        // Verify no org was created
        $this->assertDatabaseCount('cmis.orgs', 0);
    }

    #[Test]
    public function organization_name_must_be_unique()
    {
        $orgName = 'Duplicate Org Name';

        // Create first org
        Org::create([
            'name' => $orgName,
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
        ]);

        // Try to create second org with same name
        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), [
                'name' => $orgName,
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name']);

        // Verify only one org exists
        $this->assertDatabaseCount('cmis.orgs', 1);
    }

    #[Test]
    public function organization_creation_with_invalid_currency_code_fails()
    {
        $orgData = [
            'name' => 'Test Org',
            'currency' => 'INVALID',  // Should be 3 characters
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['currency']);
    }

    #[Test]
    public function user_can_view_their_organization()
    {
        // Create org and associate user
        $org = Org::create([
            'name' => 'View Test Org',
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
        ]);

        UserOrg::create([
            'user_id' => $this->user->user_id,
            'org_id' => $org->org_id,
            'role_id' => $this->ownerRole->role_id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('orgs.show', $org->org_id));

        $response->assertStatus(200)
            ->assertViewIs('orgs.show')
            ->assertViewHas('org');
    }

    #[Test]
    public function organization_stores_creator_as_owner()
    {
        $orgData = [
            'name' => 'Owner Test Org ' . Str::random(8),
        ];

        $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $org = Org::where('name', $orgData['name'])->first();

        // Verify the user is attached with owner role
        $userOrg = UserOrg::where('user_id', $this->user->user_id)
            ->where('org_id', $org->org_id)
            ->first();

        $this->assertNotNull($userOrg);
        $this->assertEquals($this->ownerRole->role_id, $userOrg->role_id);
        $this->assertTrue($userOrg->is_active);
        $this->assertNotNull($userOrg->joined_at);
    }

    #[Test]
    public function organization_creation_rolls_back_on_failure()
    {
        // This test simulates a failure scenario
        // Create an org first
        $firstOrg = Org::create([
            'name' => 'First Org',
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
        ]);

        $orgCountBefore = Org::count();
        $userOrgCountBefore = UserOrg::count();

        // Try to create org with duplicate name
        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), [
                'name' => 'First Org',  // Duplicate
            ]);

        // Verify counts remain the same (rollback occurred)
        $this->assertEquals($orgCountBefore, Org::count());
        $this->assertEquals($userOrgCountBefore, UserOrg::count());
    }

    #[Test]
    public function organization_supports_different_locales()
    {
        $locales = ['ar-BH', 'en-US', 'ar-SA', 'ar-AE'];

        foreach ($locales as $locale) {
            $orgData = [
                'name' => "Org $locale " . Str::random(6),
                'default_locale' => $locale,
            ];

            $response = $this->actingAs($this->user)
                ->post(route('orgs.store'), $orgData);

            $response->assertStatus(302)
                ->assertSessionHas('success');

            $this->assertDatabaseHas('cmis.orgs', [
                'name' => $orgData['name'],
                'default_locale' => $locale,
            ]);
        }
    }

    #[Test]
    public function organization_supports_different_currencies()
    {
        $currencies = ['BHD', 'SAR', 'AED', 'USD'];

        foreach ($currencies as $currency) {
            $orgData = [
                'name' => "Org $currency " . Str::random(6),
                'currency' => $currency,
            ];

            $response = $this->actingAs($this->user)
                ->post(route('orgs.store'), $orgData);

            $response->assertStatus(302)
                ->assertSessionHas('success');

            $this->assertDatabaseHas('cmis.orgs', [
                'name' => $orgData['name'],
                'currency' => $currency,
            ]);
        }
    }

    #[Test]
    public function organization_provider_field_is_optional()
    {
        $orgData = [
            'name' => 'No Provider Org ' . Str::random(8),
            // provider is not provided
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        $org = Org::where('name', $orgData['name'])->first();
        $this->assertNull($org->provider);
    }

    #[Test]
    public function organization_can_set_provider_field()
    {
        $orgData = [
            'name' => 'Provider Org ' . Str::random(8),
            'provider' => 'api_integration',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $response->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('cmis.orgs', [
            'name' => $orgData['name'],
            'provider' => 'api_integration',
        ]);
    }

    #[Test]
    public function created_organization_has_valid_uuid()
    {
        $orgData = [
            'name' => 'UUID Test Org ' . Str::random(8),
        ];

        $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $org = Org::where('name', $orgData['name'])->first();

        $this->assertNotNull($org);
        $this->assertTrue(Str::isUuid($org->org_id));
    }

    #[Test]
    public function created_organization_has_timestamps()
    {
        $orgData = [
            'name' => 'Timestamp Test Org ' . Str::random(8),
        ];

        $this->actingAs($this->user)
            ->post(route('orgs.store'), $orgData);

        $org = Org::where('name', $orgData['name'])->first();

        $this->assertNotNull($org->created_at);
        $this->assertNull($org->deleted_at);  // Should not be soft deleted
    }

    #[Test]
    public function organization_index_shows_all_organizations()
    {
        // Create multiple orgs
        $orgs = collect();
        for ($i = 1; $i <= 3; $i++) {
            $orgs->push(Org::create([
                'name' => "Test Org $i",
                'default_locale' => 'ar-BH',
                'currency' => 'BHD',
            ]));
        }

        $response = $this->actingAs($this->user)
            ->get(route('orgs.index'));

        $response->assertStatus(200);

        // Verify all orgs are in the view
        foreach ($orgs as $org) {
            $response->assertSee($org->name);
        }
    }

    #[Test]
    public function form_validation_provides_helpful_error_messages()
    {
        // Test with empty name
        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors(['name']);

        // Test with name too long (>255 chars)
        $response = $this->actingAs($this->user)
            ->post(route('orgs.store'), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertSessionHasErrors(['name']);
    }
}

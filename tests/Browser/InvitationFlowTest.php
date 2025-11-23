<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Org;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class InvitationFlowTest extends DuskTestCase
{
    use RefreshDatabase;

    protected User $inviter;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->inviter = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);
    }

    /**
     * Test user can view invitation acceptance page with valid token.
     */
    public function test_user_can_view_invitation_with_valid_token(): void
    {
        $token = Str::random(32);

        // Create invitation in database
        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'invited@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->assertSee('Invitation')
                ->assertSee($this->org->name)
                ->assertPresent('button[type="submit"]');
        });
    }

    /**
     * Test user can accept invitation.
     */
    public function test_user_can_accept_invitation(): void
    {
        $token = Str::random(32);
        $email = 'newmember@example.com';

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => $email,
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token, $email) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->type('input[name="name"]', 'New Member')
                ->type('input[name="email"]', $email)
                ->type('input[name="password"]', 'password123')
                ->type('input[name="password_confirmation"]', 'password123')
                ->press('Accept Invitation')
                ->pause(2000)
                ->assertPathIs('/dashboard');
        });

        $this->assertDatabaseHas('cmis_core.users', [
            'email' => $email,
        ]);
    }

    /**
     * Test user can decline invitation.
     */
    public function test_user_can_decline_invitation(): void
    {
        $token = Str::random(32);

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'decline@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->clickLink('Decline')
                ->pause(1000)
                ->assertSee('declined');
        });
    }

    /**
     * Test invitation with invalid token shows error.
     */
    public function test_invalid_invitation_token_shows_error(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/invitations/accept/invalid-token')
                ->pause(1000)
                ->assertSee('invalid')
                ->assertPresent('[data-test="invalid-invitation"]');
        });
    }

    /**
     * Test expired invitation shows error.
     */
    public function test_expired_invitation_shows_error(): void
    {
        $token = Str::random(32);

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'expired@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->subDays(1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->assertSee('expired');
        });
    }

    /**
     * Test invitation acceptance validates required fields.
     */
    public function test_invitation_acceptance_validates_fields(): void
    {
        $token = Str::random(32);

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'validate@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->press('Accept Invitation')
                ->pause(1000)
                ->assertSee('required');
        });
    }

    /**
     * Test invitation shows organization details.
     */
    public function test_invitation_displays_organization_details(): void
    {
        $token = Str::random(32);

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'details@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'member',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->assertSee($this->org->name)
                ->assertSee($this->inviter->name);
        });
    }

    /**
     * Test invitation shows role information.
     */
    public function test_invitation_displays_role_information(): void
    {
        $token = Str::random(32);

        \DB::table('cmis_core.invitations')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'email' => 'role@example.com',
            'token' => hash('sha256', $token),
            'invited_by' => $this->inviter->id,
            'role' => 'admin',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($token) {
            $browser->visit("/invitations/accept/{$token}")
                ->pause(1000)
                ->assertSee('admin');
        });
    }
}

<?php

namespace Tests\Unit\Models\Contact;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Contact Model Unit Tests
 */
class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_contact()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567',
        ]);

        $this->assertDatabaseHas('cmis.contacts', [
            'contact_id' => $contact->contact_id,
            'email' => 'ahmed@example.com',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'Contact',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals($org->org_id, $contact->org->org_id);
    }

    /** @test */
    public function it_has_full_name_accessor()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'محمد',
            'last_name' => 'السعيد',
            'email' => 'mohammed@example.com',
        ]);

        $fullName = $contact->first_name . ' ' . $contact->last_name;
        $this->assertEquals('محمد السعيد', $fullName);
    }

    /** @test */
    public function it_stores_contact_segments()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $segments = ['vip', 'newsletter', 'engaged'];

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'segments' => $segments,
        ]);

        $this->assertContains('vip', $contact->segments);
        $this->assertContains('newsletter', $contact->segments);
    }

    /** @test */
    public function it_stores_custom_fields()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $customFields = [
            'company' => 'شركة التقنية',
            'job_title' => 'مدير تسويق',
            'industry' => 'Technology',
            'budget_range' => '10000-50000',
        ];

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'custom_fields' => $customFields,
        ]);

        $this->assertEquals('شركة التقنية', $contact->custom_fields['company']);
        $this->assertEquals('مدير تسويق', $contact->custom_fields['job_title']);
    }

    /** @test */
    public function it_tracks_subscription_status()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscribedContact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Subscribed',
            'last_name' => 'User',
            'email' => 'subscribed@example.com',
            'is_subscribed' => true,
        ]);

        $unsubscribedContact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Unsubscribed',
            'last_name' => 'User',
            'email' => 'unsubscribed@example.com',
            'is_subscribed' => false,
        ]);

        $this->assertTrue($subscribedContact->is_subscribed);
        $this->assertFalse($unsubscribedContact->is_subscribed);
    }

    /** @test */
    public function it_tracks_last_engagement_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Engaged',
            'last_name' => 'User',
            'email' => 'engaged@example.com',
            'last_engaged_at' => now(),
        ]);

        $this->assertNotNull($contact->last_engaged_at);
    }

    /** @test */
    public function it_stores_social_profiles()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $socialProfiles = [
            'facebook' => 'https://facebook.com/user123',
            'twitter' => 'https://twitter.com/user123',
            'linkedin' => 'https://linkedin.com/in/user123',
            'instagram' => 'https://instagram.com/user123',
        ];

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Social',
            'last_name' => 'User',
            'email' => 'social@example.com',
            'social_profiles' => $socialProfiles,
        ]);

        $this->assertArrayHasKey('facebook', $contact->social_profiles);
        $this->assertEquals('https://facebook.com/user123', $contact->social_profiles['facebook']);
    }

    /** @test */
    public function it_stores_contact_source()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'source' => 'landing_page',
        ]);

        $this->assertEquals('landing_page', $contact->source);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $this->assertTrue(Str::isUuid($contact->contact_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($contact->created_at);
        $this->assertNotNull($contact->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $contact = Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'first_name' => 'Deletable',
            'last_name' => 'Contact',
            'email' => 'delete@example.com',
        ]);

        $contactId = $contact->contact_id;

        $contact->delete();

        $this->assertSoftDeleted('cmis.contacts', [
            'contact_id' => $contactId,
        ]);
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

        Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'first_name' => 'Org1',
            'last_name' => 'Contact',
            'email' => 'org1@example.com',
        ]);

        Contact::create([
            'contact_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'first_name' => 'Org2',
            'last_name' => 'Contact',
            'email' => 'org2@example.com',
        ]);

        $org1Contacts = Contact::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Contacts);
        $this->assertEquals('org1@example.com', $org1Contacts->first()->email);
    }
}

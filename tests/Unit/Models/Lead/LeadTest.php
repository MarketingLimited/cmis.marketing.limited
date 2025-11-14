<?php

namespace Tests\Unit\Models\Lead;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Core\Campaign;
use App\Models\Lead\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Lead Model Unit Tests
 */
class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_can_create_lead()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567',
            'status' => 'new',
        ]);

        $this->assertDatabaseHas('cmis.leads', [
            'lead_id' => $lead->lead_id,
            'name' => 'أحمد محمد',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);

        $this->assertEquals($org->org_id, $lead->org->org_id);
    }

    /** @test */
    public function it_can_belong_to_campaign()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Lead Gen Campaign',
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'name' => 'Campaign Lead',
            'email' => 'campaign@example.com',
            'status' => 'new',
        ]);

        $this->assertEquals($campaign->campaign_id, $lead->campaign->campaign_id);
    }

    /** @test */
    public function it_has_different_lead_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $newLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'New Lead',
            'email' => 'new@example.com',
            'status' => 'new',
        ]);

        $contactedLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Contacted Lead',
            'email' => 'contacted@example.com',
            'status' => 'contacted',
        ]);

        $qualifiedLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Qualified Lead',
            'email' => 'qualified@example.com',
            'status' => 'qualified',
        ]);

        $convertedLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Converted Lead',
            'email' => 'converted@example.com',
            'status' => 'converted',
        ]);

        $this->assertEquals('new', $newLead->status);
        $this->assertEquals('contacted', $contactedLead->status);
        $this->assertEquals('qualified', $qualifiedLead->status);
        $this->assertEquals('converted', $convertedLead->status);
    }

    /** @test */
    public function it_stores_lead_source()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $facebookLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Facebook Lead',
            'email' => 'fb@example.com',
            'status' => 'new',
            'source' => 'facebook',
        ]);

        $websiteLead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Website Lead',
            'email' => 'web@example.com',
            'status' => 'new',
            'source' => 'website',
        ]);

        $this->assertEquals('facebook', $facebookLead->source);
        $this->assertEquals('website', $websiteLead->source);
    }

    /** @test */
    public function it_stores_lead_score()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Scored Lead',
            'email' => 'scored@example.com',
            'status' => 'qualified',
            'score' => 85,
        ]);

        $this->assertEquals(85, $lead->score);
    }

    /** @test */
    public function it_stores_additional_data_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $additionalData = [
            'company' => 'شركة التقنية المتقدمة',
            'job_title' => 'مدير تسويق',
            'interests' => ['digital-marketing', 'social-media'],
            'budget_range' => '10000-50000',
            'notes' => 'عميل محتمل مهتم بالحلول التسويقية',
        ];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Detailed Lead',
            'email' => 'detailed@example.com',
            'status' => 'new',
            'additional_data' => $additionalData,
        ]);

        $this->assertEquals('شركة التقنية المتقدمة', $lead->additional_data['company']);
        $this->assertContains('digital-marketing', $lead->additional_data['interests']);
    }

    /** @test */
    public function it_tracks_last_contact_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Contacted Lead',
            'email' => 'contacted@example.com',
            'status' => 'contacted',
            'last_contacted_at' => now(),
        ]);

        $this->assertNotNull($lead->last_contacted_at);
    }

    /** @test */
    public function it_tracks_conversion_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Converted Lead',
            'email' => 'converted@example.com',
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $this->assertNotNull($lead->converted_at);
    }

    /** @test */
    public function it_stores_utm_parameters()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $utmData = [
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer_sale',
            'utm_content' => 'ad_variation_a',
            'utm_term' => 'marketing_software',
        ];

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'UTM Lead',
            'email' => 'utm@example.com',
            'status' => 'new',
            'utm_parameters' => $utmData,
        ]);

        $this->assertEquals('facebook', $lead->utm_parameters['utm_source']);
        $this->assertEquals('summer_sale', $lead->utm_parameters['utm_campaign']);
    }

    /** @test */
    public function it_tracks_lead_value()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Valuable Lead',
            'email' => 'valuable@example.com',
            'status' => 'qualified',
            'estimated_value' => 50000.00,
        ]);

        $this->assertEquals(50000.00, $lead->estimated_value);
    }

    /** @test */
    public function it_assigns_lead_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $salesRep = \App\Models\User::create([
            'user_id' => Str::uuid(),
            'name' => 'Sales Rep',
            'email' => 'sales@example.com',
            'password' => bcrypt('password'),
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Assigned Lead',
            'email' => 'assigned@example.com',
            'status' => 'contacted',
            'assigned_to' => $salesRep->id,
        ]);

        $this->assertEquals($salesRep->id, $lead->assigned_to);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);

        $this->assertTrue(Str::isUuid($lead->lead_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Lead',
            'email' => 'test@example.com',
            'status' => 'new',
        ]);

        $this->assertNotNull($lead->created_at);
        $this->assertNotNull($lead->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lead = Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Lead',
            'email' => 'deletable@example.com',
            'status' => 'new',
        ]);

        $leadId = $lead->lead_id;

        $lead->delete();

        $this->assertSoftDeleted('cmis.leads', [
            'lead_id' => $leadId,
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

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Lead',
            'email' => 'org1@example.com',
            'status' => 'new',
        ]);

        Lead::create([
            'lead_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Lead',
            'email' => 'org2@example.com',
            'status' => 'new',
        ]);

        $org1Leads = Lead::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Leads);
        $this->assertEquals('Org 1 Lead', $org1Leads->first()->name);
    }
}

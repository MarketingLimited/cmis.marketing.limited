<?php

namespace Tests\Feature\Influencer;

use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerCampaign;
use App\Models\Influencer\InfluencerPayment;
use App\Models\Core\Org;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Org $org;
    protected Influencer $influencer;
    protected InfluencerCampaign $campaign;

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

        $this->influencer = Influencer::factory()->create([
            'org_id' => $this->org->org_id,
        ]);

        $this->campaign = InfluencerCampaign::factory()->create([
            'org_id' => $this->org->org_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);
    }

    /** @test */
    public function it_can_list_payments()
    {
        InfluencerPayment::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.payments.index'));

        $response->assertOk();
        $response->assertViewIs('influencer.payments.index');
        $response->assertViewHas('payments');
    }

    /** @test */
    public function it_can_create_a_payment()
    {
        $data = [
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'amount' => 5000,
            'currency' => 'USD',
            'status' => 'pending',
            'due_date' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->user)
            ->post(route('influencer.payments.store'), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
            'org_id' => $this->org->org_id,
            'amount' => 5000,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_show_a_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.payments.show', $payment->payment_id));

        $response->assertOk();
        $response->assertViewIs('influencer.payments.show');
        $response->assertViewHas('payment');
    }

    /** @test */
    public function it_can_update_a_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'amount' => 5000,
        ]);

        $data = [
            'amount' => 6000,
            'status' => 'processing',
        ];

        $response = $this->actingAs($this->user)
            ->put(route('influencer.payments.update', $payment->payment_id), $data);

        $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
            'payment_id' => $payment->payment_id,
            'amount' => 6000,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_can_delete_a_pending_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('influencer.payments.destroy', $payment->payment_id));

        $this->assertSoftDeleted('cmis_influencer.influencer_payments', [
            'payment_id' => $payment->payment_id,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_cannot_delete_completed_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('influencer.payments.destroy', $payment->payment_id));

        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_process_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.payments.process', $payment->payment_id), [
                'payment_method' => 'bank_transfer',
                'transaction_id' => 'TXN-12345',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
            'payment_id' => $payment->payment_id,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_can_complete_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.payments.complete', $payment->payment_id), [
                'transaction_id' => 'TXN-12345',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
            'payment_id' => $payment->payment_id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_can_mark_payment_as_failed()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'processing',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.payments.fail', $payment->payment_id), [
                'failure_reason' => 'Insufficient funds',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
            'payment_id' => $payment->payment_id,
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_can_generate_invoice()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.payments.generateInvoice', $payment->payment_id));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'invoice_number',
                'issue_date',
                'due_date',
                'payment_details',
                'influencer',
                'campaign',
            ],
        ]);
    }

    /** @test */
    public function it_can_get_payment_analytics()
    {
        InfluencerPayment::factory()->count(10)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.payments.analytics'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'by_method',
                'by_status',
                'by_month',
            ],
        ]);
    }

    /** @test */
    public function it_can_bulk_update_payments()
    {
        $payments = InfluencerPayment::factory()->count(3)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.payments.bulkUpdate'), [
                'payment_ids' => $payments->pluck('payment_id')->toArray(),
                'status' => 'processing',
            ]);

        $response->assertOk();

        foreach ($payments as $payment) {
            $this->assertDatabaseHas('cmis_influencer.influencer_payments', [
                'payment_id' => $payment->payment_id,
                'status' => 'processing',
            ]);
        }
    }

    /** @test */
    public function it_can_get_overdue_payments()
    {
        InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.payments.overdue'));

        $response->assertOk();
    }

    /** @test */
    public function it_can_get_upcoming_payments()
    {
        InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
            'due_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.payments.upcoming', ['days' => 30]));

        $response->assertOk();
    }

    /** @test */
    public function it_can_export_payment_report()
    {
        InfluencerPayment::factory()->count(5)->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('influencer.payments.export'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'summary',
                'payments',
                'exported_at',
            ],
        ]);
    }

    /** @test */
    public function it_can_reconcile_payment()
    {
        $payment = InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('influencer.payments.reconcile', $payment->payment_id), [
                'bank_statement_id' => 'STMT-12345',
                'reconciled_amount' => 5000,
            ]);

        $response->assertOk();
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

        $otherPayment = InfluencerPayment::factory()->create([
            'org_id' => $otherOrg->org_id,
            'campaign_id' => $otherCampaign->campaign_id,
            'influencer_id' => $otherInfluencer->influencer_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.payments.show', $otherPayment->payment_id));

        $response->assertNotFound();
    }

    /** @test */
    public function it_filters_payments_by_status()
    {
        InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'completed',
        ]);

        InfluencerPayment::factory()->create([
            'org_id' => $this->org->org_id,
            'campaign_id' => $this->campaign->campaign_id,
            'influencer_id' => $this->influencer->influencer_id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('influencer.payments.index', ['status' => 'completed']));

        $response->assertOk();
    }
}

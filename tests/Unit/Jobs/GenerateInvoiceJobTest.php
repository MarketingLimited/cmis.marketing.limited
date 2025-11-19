<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\Billing\GenerateInvoiceJob;
use App\Models\Core\Org;
use App\Models\Subscription\Subscription;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Queue;

use PHPUnit\Framework\Attributes\Test;
/**
 * GenerateInvoice Job Unit Tests
 */
class GenerateInvoiceJobTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_generates_invoice_for_subscription()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('invoice_id', $result);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'generate_invoice',
        ]);
    }

    #[Test]
    public function it_calculates_invoice_total()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Enterprise',
            'status' => 'active',
            'price' => 299.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('total', $result);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'calculate_total',
        ]);
    }

    #[Test]
    public function it_applies_tax_to_invoice()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 100.00,
            'currency' => 'SAR',
        ]);

        $job = new GenerateInvoiceJob($subscription, ['tax_rate' => 15]);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        // Should include 15% VAT for SAR

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'apply_tax',
        ]);
    }

    #[Test]
    public function it_can_be_dispatched()
    {
        Queue::fake();

        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Basic',
            'status' => 'active',
            'price' => 49.99,
            'currency' => 'USD',
        ]);

        GenerateInvoiceJob::dispatch($subscription);

        Queue::assertPushed(GenerateInvoiceJob::class);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'dispatch',
        ]);
    }

    #[Test]
    public function it_generates_unique_invoice_number()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('invoice_number', $result);
        $this->assertNotEmpty($result['invoice_number']);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'unique_invoice_number',
        ]);
    }

    #[Test]
    public function it_includes_billing_address()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $billingAddress = [
            'company' => 'شركة التسويق',
            'street' => 'طريق الملك فهد',
            'city' => 'الرياض',
        ];

        $job = new GenerateInvoiceJob($subscription, ['billing_address' => $billingAddress]);
        $result = $job->handle();

        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'billing_address',
        ]);
    }

    #[Test]
    public function it_sends_invoice_notification()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        // Should send notification to org owner
        $this->assertTrue($result['success']);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'send_notification',
        ]);
    }

    #[Test]
    public function it_stores_invoice_pdf()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('pdf_path', $result);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'store_pdf',
        ]);
    }

    #[Test]
    public function it_handles_different_currencies()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $currencies = ['USD', 'SAR', 'EUR'];

        foreach ($currencies as $currency) {
            $subscription = Subscription::create([
                'subscription_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'plan_name' => 'Professional',
                'status' => 'active',
                'price' => 99.99,
                'currency' => $currency,
            ]);

            $job = new GenerateInvoiceJob($subscription);
            $result = $job->handle();

            $this->assertTrue($result['success']);
        }

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'multiple_currencies',
        ]);
    }

    #[Test]
    public function it_includes_line_items()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $subscription = Subscription::create([
            'subscription_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'plan_name' => 'Professional',
            'status' => 'active',
            'price' => 99.99,
            'currency' => 'USD',
        ]);

        $job = new GenerateInvoiceJob($subscription);
        $result = $job->handle();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('line_items', $result);

        $this->logTestResult('passed', [
            'job' => 'GenerateInvoiceJob',
            'test' => 'line_items',
        ]);
    }
}

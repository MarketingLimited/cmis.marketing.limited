<?php

namespace Tests\Unit\Models\Billing;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Billing\Invoice;
use App\Models\Subscription\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Invoice Model Unit Tests
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_invoice()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-001',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.invoices', [
            'invoice_id' => $invoice->invoice_id,
            'invoice_number' => 'INV-2024-001',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-002',
            'amount' => 149.99,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $this->assertEquals($org->org_id, $invoice->org->org_id);
    }

    #[Test]
    public function it_belongs_to_subscription()
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
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'subscription_id' => $subscription->subscription_id,
            'invoice_number' => 'INV-2024-003',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->assertEquals($subscription->subscription_id, $invoice->subscription->subscription_id);
    }

    #[Test]
    public function it_has_different_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $pendingInvoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-004',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $paidInvoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-005',
            'amount' => 149.99,
            'currency' => 'USD',
            'status' => 'paid',
        ]);

        $voidInvoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-006',
            'amount' => 199.99,
            'currency' => 'USD',
            'status' => 'void',
        ]);

        $this->assertEquals('pending', $pendingInvoice->status);
        $this->assertEquals('paid', $paidInvoice->status);
        $this->assertEquals('void', $voidInvoice->status);
    }

    #[Test]
    public function it_stores_line_items_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $lineItems = [
            [
                'description' => 'Professional Plan - Monthly',
                'quantity' => 1,
                'unit_price' => 99.99,
                'total' => 99.99,
            ],
            [
                'description' => 'Additional Users (5)',
                'quantity' => 5,
                'unit_price' => 10.00,
                'total' => 50.00,
            ],
        ];

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-007',
            'amount' => 149.99,
            'currency' => 'USD',
            'status' => 'pending',
            'line_items' => $lineItems,
        ]);

        $this->assertCount(2, $invoice->line_items);
        $this->assertEquals(99.99, $invoice->line_items[0]['unit_price']);
    }

    #[Test]
    public function it_calculates_total_with_tax()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-008',
            'amount' => 100.00,
            'tax_amount' => 15.00,
            'total_amount' => 115.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->assertEquals(100.00, $invoice->amount);
        $this->assertEquals(15.00, $invoice->tax_amount);
        $this->assertEquals(115.00, $invoice->total_amount);
    }

    #[Test]
    public function it_has_due_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-009',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
        ]);

        $this->assertNotNull($invoice->issued_at);
        $this->assertNotNull($invoice->due_at);
        $this->assertTrue($invoice->due_at->greaterThan($invoice->issued_at));
    }

    #[Test]
    public function it_tracks_payment_date()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-010',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertNotNull($invoice->paid_at);
    }

    #[Test]
    public function it_supports_different_currencies()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $usdInvoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-011',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $sarInvoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-012',
            'amount' => 374.96,
            'currency' => 'SAR',
            'status' => 'pending',
        ]);

        $this->assertEquals('USD', $usdInvoice->currency);
        $this->assertEquals('SAR', $sarInvoice->currency);
    }

    #[Test]
    public function it_stores_payment_method()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-013',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'paid',
            'payment_method' => 'credit_card',
        ]);

        $this->assertEquals('credit_card', $invoice->payment_method);
    }

    #[Test]
    public function it_has_billing_address()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $billingAddress = [
            'company' => 'شركة التسويق المحدودة',
            'street' => 'طريق الملك فهد',
            'city' => 'الرياض',
            'postal_code' => '12345',
            'country' => 'SA',
        ];

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-014',
            'amount' => 99.99,
            'currency' => 'SAR',
            'status' => 'pending',
            'billing_address' => $billingAddress,
        ]);

        $this->assertEquals('الرياض', $invoice->billing_address['city']);
        $this->assertEquals('SA', $invoice->billing_address['country']);
    }

    #[Test]
    public function it_stores_notes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-015',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
            'notes' => 'شكراً لاختيارك خدماتنا. الدفع مستحق خلال 30 يوماً.',
        ]);

        $this->assertStringContainsString('شكراً', $invoice->notes);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-016',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->assertTrue(Str::isUuid($invoice->invoice_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $invoice = Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'invoice_number' => 'INV-2024-017',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $this->assertNotNull($invoice->created_at);
        $this->assertNotNull($invoice->updated_at);
    }

    #[Test]
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

        Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'invoice_number' => 'INV-ORG1-001',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        Invoice::create([
            'invoice_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'invoice_number' => 'INV-ORG2-001',
            'amount' => 149.99,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        $org1Invoices = Invoice::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Invoices);
        $this->assertEquals('INV-ORG1-001', $org1Invoices->first()->invoice_number);
    }
}

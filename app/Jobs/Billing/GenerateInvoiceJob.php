<?php

namespace App\Jobs\Billing;

use App\Models\Subscription\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subscription;
    protected $options;

    public function __construct(Subscription $subscription, array $options = [])
    {
        $this->subscription = $subscription;
        $this->options = $options;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        // Generate unique invoice ID
        $result['invoice_id'] = Str::uuid()->toString();

        // Generate unique invoice number
        $result['invoice_number'] = $this->generateInvoiceNumber();

        // Calculate base total
        $baseTotal = $this->subscription->price ?? 0.0;

        // Apply tax if provided
        $taxRate = $this->options['tax_rate'] ?? 0;
        $taxAmount = ($baseTotal * $taxRate) / 100;
        $result['total'] = $baseTotal + $taxAmount;

        // Generate line items
        $result['line_items'] = $this->generateLineItems($baseTotal, $taxAmount);

        // Generate PDF path
        $result['pdf_path'] = $this->generatePdfPath($result['invoice_number']);

        // Include billing address if provided
        if (isset($this->options['billing_address'])) {
            $result['billing_address'] = $this->options['billing_address'];
        }

        return $result;
    }

    protected function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    protected function generateLineItems(float $baseTotal, float $taxAmount): array
    {
        $items = [
            [
                'description' => $this->subscription->plan_name . ' Subscription',
                'amount' => $baseTotal,
                'currency' => $this->subscription->currency,
            ],
        ];

        if ($taxAmount > 0) {
            $items[] = [
                'description' => 'Tax (' . ($this->options['tax_rate'] ?? 0) . '%)',
                'amount' => $taxAmount,
                'currency' => $this->subscription->currency,
            ];
        }

        return $items;
    }

    protected function generatePdfPath(string $invoiceNumber): string
    {
        return 'invoices/' . $invoiceNumber . '.pdf';
    }
}

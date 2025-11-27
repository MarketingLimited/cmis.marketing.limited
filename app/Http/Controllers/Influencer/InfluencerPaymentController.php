<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Influencer\InfluencerPayment;
use App\Models\Influencer\InfluencerCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InfluencerPaymentController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $payments = InfluencerPayment::where('org_id', $orgId)
            ->when($request->campaign_id, fn($q) => $q->where('campaign_id', $request->campaign_id))
            ->when($request->influencer_id, fn($q) => $q->where('influencer_id', $request->influencer_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_method, fn($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->search, fn($q) => $q->where('invoice_number', 'like', "%{$request->search}%"))
            ->with(['campaign', 'influencer'])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($payments, 'Payments retrieved successfully');
        }

        return view('influencer.payments.index', compact('payments'));
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), InfluencerPayment::createRules(), InfluencerPayment::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $payment = InfluencerPayment::create(array_merge($request->all(), [
            'org_id' => session('current_org_id'),
            'invoice_number' => $this->generateInvoiceNumber(),
        ]));

        if ($request->expectsJson()) {
            return $this->created($payment, 'Payment created successfully');
        }

        return redirect()->route('influencer.payments.show', $payment->payment_id)
            ->with('success', 'Payment created successfully');
    }

    /**
     * Display the specified payment
     */
    public function show(string $id)
    {
        $payment = InfluencerPayment::with(['campaign', 'influencer'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($payment, 'Payment retrieved successfully');
        }

        return view('influencer.payments.show', compact('payment'));
    }

    /**
     * Update the specified payment
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), InfluencerPayment::updateRules(), InfluencerPayment::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $payment = InfluencerPayment::findOrFail($id);
        $payment->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($payment, 'Payment updated successfully');
        }

        return redirect()->route('influencer.payments.show', $payment->payment_id)
            ->with('success', 'Payment updated successfully');
    }

    /**
     * Remove the specified payment
     */
    public function destroy(string $id)
    {
        $payment = InfluencerPayment::findOrFail($id);

        if ($payment->status === 'completed') {
            return $this->error('Cannot delete completed payments', 400);
        }

        $payment->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Payment deleted successfully');
        }

        return redirect()->route('influencer.payments.index')
            ->with('success', 'Payment deleted successfully');
    }

    /**
     * Process payment
     */
    public function process(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:bank_transfer,paypal,stripe,wire_transfer,check',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $payment = InfluencerPayment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return $this->error('Only pending payments can be processed', 400);
        }

        $payment->update([
            'status' => 'processing',
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        return $this->success($payment, 'Payment processing initiated successfully');
    }

    /**
     * Mark payment as completed
     */
    public function complete(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string|max:255',
            'paid_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $payment = InfluencerPayment::findOrFail($id);

        if (!in_array($payment->status, ['pending', 'processing'])) {
            return $this->error('Only pending or processing payments can be completed', 400);
        }

        $payment->update([
            'status' => 'completed',
            'transaction_id' => $request->transaction_id,
            'paid_at' => $request->paid_at ?? now(),
        ]);

        return $this->success($payment, 'Payment completed successfully');
    }

    /**
     * Mark payment as failed
     */
    public function fail(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'failure_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $payment = InfluencerPayment::findOrFail($id);

        $payment->update([
            'status' => 'failed',
            'notes' => $request->failure_reason,
        ]);

        return $this->success($payment, 'Payment marked as failed');
    }

    /**
     * Generate invoice for payment
     */
    public function generateInvoice(string $id)
    {
        $payment = InfluencerPayment::with(['campaign', 'influencer'])
            ->findOrFail($id);

        $invoice = [
            'invoice_number' => $payment->invoice_number,
            'issue_date' => now()->toDateString(),
            'due_date' => $payment->due_date,
            'payment_details' => [
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
            ],
            'influencer' => [
                'name' => $payment->influencer->name,
                'email' => $payment->influencer->email,
                'platform' => $payment->influencer->platform,
            ],
            'campaign' => [
                'name' => $payment->campaign->name,
                'type' => $payment->campaign->campaign_type,
            ],
            'line_items' => $payment->line_items ?? [],
            'notes' => $payment->notes,
            'payment_terms' => $payment->payment_terms,
        ];

        return $this->success($invoice, 'Invoice generated successfully');
    }

    /**
     * Get payment analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $payments = InfluencerPayment::where('org_id', $orgId)->get();

        $totalPayments = $payments->count();
        $completedPayments = $payments->where('status', 'completed')->count();
        $pendingPayments = $payments->where('status', 'pending')->count();
        $failedPayments = $payments->where('status', 'failed')->count();
        $totalAmount = $payments->sum('amount');
        $paidAmount = $payments->where('status', 'completed')->sum('amount');
        $pendingAmount = $payments->where('status', 'pending')->sum('amount');

        $analytics = [
            'summary' => [
                'total_payments' => $totalPayments,
                'completed_payments' => $completedPayments,
                'pending_payments' => $pendingPayments,
                'failed_payments' => $failedPayments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'success_rate' => $totalPayments > 0 ? round(($completedPayments / $totalPayments) * 100, 2) : 0,
            ],
            'by_method' => $payments->where('status', 'completed')->groupBy('payment_method')->map->count(),
            'by_status' => $payments->groupBy('status')->map->count(),
            'by_month' => $payments
                ->where('status', 'completed')
                ->groupBy(fn($p) => $p->paid_at?->format('Y-m'))
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount'),
                ]),
        ];

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Payment analytics retrieved successfully');
        }

        return view('influencer.payments.analytics', compact('analytics'));
    }

    /**
     * Bulk update payments
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_ids' => 'required|array',
            'payment_ids.*' => 'uuid',
            'status' => 'nullable|in:pending,processing,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $updated = InfluencerPayment::where('org_id', $orgId)
            ->whereIn('payment_id', $request->payment_ids)
            ->update(array_filter([
                'status' => $request->status,
            ]));

        return $this->success([
            'updated_count' => $updated,
        ], 'Payments updated successfully');
    }

    /**
     * Get overdue payments
     */
    public function overdue(Request $request)
    {
        $orgId = session('current_org_id');

        $overduePayments = InfluencerPayment::where('org_id', $orgId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with(['campaign', 'influencer'])
            ->orderBy('due_date', 'asc')
            ->get();

        return $this->success($overduePayments, 'Overdue payments retrieved successfully');
    }

    /**
     * Get upcoming payments
     */
    public function upcoming(Request $request)
    {
        $orgId = session('current_org_id');
        $days = $request->get('days', 30);

        $upcomingPayments = InfluencerPayment::where('org_id', $orgId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->with(['campaign', 'influencer'])
            ->orderBy('due_date', 'asc')
            ->get();

        return $this->success($upcomingPayments, 'Upcoming payments retrieved successfully');
    }

    /**
     * Export payment report
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'format' => 'nullable|in:csv,json,pdf',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $query = InfluencerPayment::where('org_id', $orgId);

        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $payments = $query->with(['campaign', 'influencer'])->get();

        $export = [
            'summary' => [
                'total_payments' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'paid_amount' => $payments->where('status', 'completed')->sum('amount'),
                'pending_amount' => $payments->where('status', 'pending')->sum('amount'),
            ],
            'payments' => $payments->map(fn($p) => [
                'invoice_number' => $p->invoice_number,
                'influencer' => $p->influencer->name,
                'campaign' => $p->campaign->name,
                'amount' => $p->amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'due_date' => $p->due_date,
                'paid_at' => $p->paid_at,
            ]),
            'exported_at' => now()->toIso8601String(),
        ];

        return $this->success($export, 'Payment report exported successfully');
    }

    /**
     * Reconcile payment with bank statement
     */
    public function reconcile(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'bank_statement_id' => 'required|string|max:255',
            'reconciled_amount' => 'required|numeric|min:0',
            'reconciliation_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $payment = InfluencerPayment::findOrFail($id);

        $payment->update([
            'reconciled_at' => now(),
            'reconciliation_data' => [
                'bank_statement_id' => $request->bank_statement_id,
                'reconciled_amount' => $request->reconciled_amount,
                'notes' => $request->reconciliation_notes,
                'reconciled_by' => auth()->id(),
            ],
        ]);

        return $this->success($payment, 'Payment reconciled successfully');
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));

        return "{$prefix}-{$date}-{$random}";
    }
}

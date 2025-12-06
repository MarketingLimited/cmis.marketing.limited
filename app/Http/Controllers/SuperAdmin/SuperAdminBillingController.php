<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use App\Models\Billing\Invoice;
use App\Models\Billing\Payment;
use App\Models\Core\Org;
use App\Models\Subscription\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin Billing Controller
 *
 * Manages invoices, payments, and revenue analytics.
 */
class SuperAdminBillingController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * Display billing dashboard with overview statistics.
     */
    public function index(Request $request)
    {
        // Revenue statistics
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        $stats = [
            'total_revenue' => Invoice::paid()->sum('total_amount') ?? 0,
            'current_month_revenue' => Invoice::paid()
                ->where('paid_at', '>=', $currentMonth)
                ->sum('total_amount') ?? 0,
            'last_month_revenue' => Invoice::paid()
                ->whereBetween('paid_at', [$lastMonth, $currentMonth])
                ->sum('total_amount') ?? 0,
            'pending_invoices' => Invoice::pending()->count(),
            'overdue_invoices' => Invoice::overdue()->count(),
            'total_invoices' => Invoice::count(),
        ];

        // Calculate growth percentage
        $stats['growth_percentage'] = $stats['last_month_revenue'] > 0
            ? round((($stats['current_month_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100, 1)
            : 0;

        // Monthly revenue for the last 12 months (for chart)
        $monthlyRevenue = Invoice::paid()
            ->where('paid_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_TRUNC('month', paid_at) as month, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => \Carbon\Carbon::parse($item->month)->format('M Y'),
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Recent invoices
        $recentInvoices = Invoice::with(['org', 'subscription.plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Overdue invoices
        $overdueInvoices = Invoice::with(['org'])
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        // Revenue by plan
        $revenueByPlan = DB::table('cmis.invoices')
            ->join('cmis.subscriptions', 'cmis.invoices.subscription_id', '=', 'cmis.subscriptions.subscription_id')
            ->join('cmis.plans', 'cmis.subscriptions.plan_id', '=', 'cmis.plans.plan_id')
            ->where('cmis.invoices.status', 'paid')
            ->selectRaw('cmis.plans.name, SUM(cmis.invoices.total_amount) as revenue')
            ->groupBy('cmis.plans.plan_id', 'cmis.plans.name')
            ->orderBy('revenue', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return $this->success([
                'stats' => $stats,
                'monthly_revenue' => $monthlyRevenue,
                'recent_invoices' => $recentInvoices,
                'overdue_invoices' => $overdueInvoices,
                'revenue_by_plan' => $revenueByPlan,
            ]);
        }

        return view('super-admin.billing.index', compact(
            'stats',
            'monthlyRevenue',
            'recentInvoices',
            'overdueInvoices',
            'revenueByPlan'
        ));
    }

    /**
     * Display all invoices with filtering.
     */
    public function invoices(Request $request)
    {
        $query = Invoice::with(['org', 'subscription.plan']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ilike', "%{$search}%")
                    ->orWhereHas('org', function ($orgQ) use ($search) {
                        $orgQ->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->pending();
                    break;
                case 'paid':
                    $query->paid();
                    break;
                case 'overdue':
                    $query->overdue();
                    break;
                case 'cancelled':
                    $query->where('status', Invoice::STATUS_CANCELLED);
                    break;
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $invoices = $query->paginate($request->get('per_page', 20));

        // Stats
        $stats = [
            'total' => Invoice::count(),
            'pending' => Invoice::pending()->count(),
            'paid' => Invoice::paid()->count(),
            'overdue' => Invoice::overdue()->count(),
        ];

        if ($request->expectsJson()) {
            return $this->paginated($invoices);
        }

        return view('super-admin.billing.invoices', compact('invoices', 'stats'));
    }

    /**
     * Display a specific invoice.
     */
    public function showInvoice(Request $request, string $invoiceId)
    {
        $invoice = Invoice::with(['org', 'subscription.plan', 'payments'])
            ->findOrFail($invoiceId);

        if ($request->expectsJson()) {
            return $this->success($invoice);
        }

        return view('super-admin.billing.show', compact('invoice'));
    }

    /**
     * Create a new invoice.
     */
    public function createInvoice(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'subscription_id' => 'nullable|uuid|exists:cmis.subscriptions,subscription_id',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string|max:1000',
            'due_date' => 'required|date|after_or_equal:today',
            'billing_period_start' => 'nullable|date',
            'billing_period_end' => 'nullable|date|after_or_equal:billing_period_start',
        ]);

        $invoice = Invoice::create($validated);

        $this->logAction('invoice_created', 'invoice', $invoice->invoice_id, null, [
            'org_id' => $validated['org_id'],
            'amount' => $validated['amount'],
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $this->created(
            $invoice->load(['org', 'subscription.plan']),
            __('super_admin.billing.invoice_created')
        );
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Request $request, string $invoiceId)
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice->isPaid()) {
            return $this->error(__('super_admin.billing.invoice_already_paid'), 400);
        }

        $invoice->markAsPaid(
            $validated['payment_method'] ?? null,
            $validated['payment_reference'] ?? null
        );

        // Create payment record
        Payment::create([
            'org_id' => $invoice->org_id,
            'invoice_id' => $invoice->invoice_id,
            'amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'status' => Payment::STATUS_COMPLETED,
            'payment_method' => $validated['payment_method'] ?? 'manual',
            'paid_at' => now(),
        ]);

        $this->logAction('invoice_marked_paid', 'invoice', $invoiceId, null, [
            'payment_method' => $validated['payment_method'] ?? 'manual',
            'amount' => $invoice->total_amount,
        ]);

        return $this->success($invoice->fresh(), __('super_admin.billing.invoice_marked_paid'));
    }

    /**
     * Send invoice reminder.
     */
    public function sendReminder(Request $request, string $invoiceId)
    {
        $invoice = Invoice::with(['org'])->findOrFail($invoiceId);

        if ($invoice->isPaid()) {
            return $this->error(__('super_admin.billing.invoice_already_paid'), 400);
        }

        // Here you would implement email sending logic
        // For now, we just log the action

        $this->logAction('invoice_reminder_sent', 'invoice', $invoiceId, null, [
            'org_id' => $invoice->org_id,
            'org_name' => $invoice->org->name ?? 'Unknown',
        ]);

        return $this->success(null, __('super_admin.billing.reminder_sent'));
    }

    /**
     * Cancel an invoice.
     */
    public function cancelInvoice(Request $request, string $invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice->isPaid()) {
            return $this->error(__('super_admin.billing.cannot_cancel_paid_invoice'), 400);
        }

        $invoice->cancel();

        $this->logAction('invoice_cancelled', 'invoice', $invoiceId, null, [
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $this->success($invoice->fresh(), __('super_admin.billing.invoice_cancelled'));
    }

    /**
     * Display all payments.
     */
    public function payments(Request $request)
    {
        $query = Payment::with(['org', 'invoice']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'ilike', "%{$search}%")
                    ->orWhereHas('org', function ($orgQ) use ($search) {
                        $orgQ->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('paid_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('paid_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'paid_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $payments = $query->paginate($request->get('per_page', 20));

        // Stats
        $stats = [
            'total_payments' => Payment::count(),
            'completed' => Payment::completed()->count(),
            'pending' => Payment::pending()->count(),
            'failed' => Payment::failed()->count(),
            'total_amount' => Payment::completed()->sum('amount'),
        ];

        if ($request->expectsJson()) {
            return $this->paginated($payments);
        }

        return view('super-admin.billing.payments', compact('payments', 'stats'));
    }

    /**
     * Process refund for a payment.
     */
    public function refund(Request $request, string $paymentId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        $payment = Payment::findOrFail($paymentId);

        if (!$payment->isCompleted()) {
            return $this->error(__('super_admin.billing.cannot_refund_incomplete_payment'), 400);
        }

        if ($payment->isRefunded()) {
            return $this->error(__('super_admin.billing.payment_already_refunded'), 400);
        }

        if ($validated['amount'] > $payment->amount) {
            return $this->error(__('super_admin.billing.refund_exceeds_payment'), 400);
        }

        $payment->refund($validated['amount'], $validated['reason']);

        // Update invoice status if fully refunded
        if ($payment->invoice && $validated['amount'] >= $payment->amount) {
            $payment->invoice->update(['status' => Invoice::STATUS_REFUNDED]);
        }

        $this->logAction('payment_refunded', 'payment', $paymentId, null, [
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'invoice_id' => $payment->invoice_id,
        ]);

        return $this->success($payment->fresh(), __('super_admin.billing.refund_processed'));
    }

    /**
     * Display revenue analytics.
     */
    public function revenue(Request $request)
    {
        $period = $request->get('period', 'year'); // year, quarter, month

        // Get date range based on period
        switch ($period) {
            case 'month':
                $startDate = now()->startOfMonth();
                $groupBy = 'day';
                break;
            case 'quarter':
                $startDate = now()->startOfQuarter();
                $groupBy = 'week';
                break;
            default:
                $startDate = now()->startOfYear();
                $groupBy = 'month';
        }

        // Revenue over time
        $revenueOverTime = Invoice::paid()
            ->where('paid_at', '>=', $startDate)
            ->selectRaw("DATE_TRUNC('{$groupBy}', paid_at) as period, SUM(total_amount) as revenue")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(function ($item) use ($groupBy) {
                $format = $groupBy === 'day' ? 'M d' : ($groupBy === 'week' ? 'W' : 'M Y');
                return [
                    'period' => \Carbon\Carbon::parse($item->period)->format($format),
                    'revenue' => (float) $item->revenue,
                ];
            });

        // Revenue by organization (top 10)
        $revenueByOrg = DB::table('cmis.invoices')
            ->join('cmis.orgs', 'cmis.invoices.org_id', '=', 'cmis.orgs.org_id')
            ->where('cmis.invoices.status', 'paid')
            ->where('cmis.invoices.paid_at', '>=', $startDate)
            ->selectRaw('cmis.orgs.name, SUM(cmis.invoices.total_amount) as revenue')
            ->groupBy('cmis.orgs.org_id', 'cmis.orgs.name')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get();

        // Revenue by currency
        $revenueByCurrency = Invoice::paid()
            ->where('paid_at', '>=', $startDate)
            ->selectRaw('currency, SUM(total_amount) as revenue')
            ->groupBy('currency')
            ->orderBy('revenue', 'desc')
            ->get();

        // Payment method breakdown
        $paymentMethods = Payment::completed()
            ->where('paid_at', '>=', $startDate)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        // Summary stats
        $stats = [
            'total_revenue' => Invoice::paid()->where('paid_at', '>=', $startDate)->sum('total_amount') ?? 0,
            'invoice_count' => Invoice::paid()->where('paid_at', '>=', $startDate)->count(),
            'average_invoice' => Invoice::paid()->where('paid_at', '>=', $startDate)->avg('total_amount') ?? 0,
            'outstanding_amount' => Invoice::pending()->sum('total_amount') ?? 0,
        ];

        if ($request->expectsJson()) {
            return $this->success([
                'revenue_over_time' => $revenueOverTime,
                'revenue_by_org' => $revenueByOrg,
                'revenue_by_currency' => $revenueByCurrency,
                'payment_methods' => $paymentMethods,
                'stats' => $stats,
            ]);
        }

        return view('super-admin.billing.revenue', compact(
            'revenueOverTime',
            'revenueByOrg',
            'revenueByCurrency',
            'paymentMethods',
            'stats',
            'period'
        ));
    }

    /**
     * Get form for creating an invoice.
     */
    public function createInvoiceForm(Request $request)
    {
        $organizations = Org::orderBy('name')->get(['org_id', 'name']);
        $subscriptions = Subscription::with('plan')
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('super-admin.billing.create', compact('organizations', 'subscriptions'));
    }
}

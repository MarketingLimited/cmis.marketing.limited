@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.invoice_details'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.invoices') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.invoices') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ $invoice->invoice_number ?? substr($invoice->invoice_id, 0, 8) }}</span>
@endsection

@section('content')
<div x-data="invoiceDetails()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $invoice->invoice_number ?? 'INV-' . substr($invoice->invoice_id, 0, 8) }}
                </h1>
                @php
                    $statusClasses = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'sent' => 'bg-blue-100 text-blue-800',
                        'paid' => 'bg-green-100 text-green-800',
                        'overdue' => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-800',
                        'refunded' => 'bg-purple-100 text-purple-800',
                    ];
                    $statusClass = $statusClasses[$invoice->status] ?? 'bg-gray-100 text-gray-800';
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusClass }}">
                    {{ __('super_admin.billing.' . $invoice->status) }}
                </span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('super_admin.billing.created') }}: {{ $invoice->created_at->format('F d, Y H:i') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(!$invoice->isPaid())
            <button @click="markAsPaid()" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check"></i>
                <span>{{ __('super_admin.billing.mark_as_paid') }}</span>
            </button>
            <button @click="sendReminder()" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                <i class="fas fa-bell"></i>
                <span>{{ __('super_admin.billing.send_reminder') }}</span>
            </button>
            <button @click="cancelInvoice()" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-times"></i>
                <span>{{ __('common.cancel') }}</span>
            </button>
            @endif
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-print"></i>
                <span>{{ __('common.print') }}</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Invoice Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Main Invoice Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.title') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('super_admin.billing.invoice') }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-sm text-gray-500">{{ __('super_admin.billing.invoice_number') }}</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $invoice->invoice_number ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('super_admin.billing.bill_to') }}</h4>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $invoice->org->name ?? __('common.unknown') }}</p>
                        @if($invoice->org)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->org->email ?? '' }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->org->phone ?? '' }}</p>
                        @endif
                    </div>
                    <div class="text-end">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('super_admin.billing.invoice_date') }}</h4>
                        <p class="text-gray-900 dark:text-white">{{ $invoice->created_at->format('M d, Y') }}</p>
                        @if($invoice->due_date)
                        <h4 class="text-sm font-medium text-gray-500 mt-4 mb-2">{{ __('super_admin.billing.due_date') }}</h4>
                        <p class="text-gray-900 dark:text-white {{ $invoice->isOverdue() ? 'text-red-600' : '' }}">
                            {{ $invoice->due_date->format('M d, Y') }}
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Billing Period -->
                @if($invoice->billing_period_start && $invoice->billing_period_end)
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('super_admin.billing.billing_period') }}</h4>
                    <p class="text-gray-900 dark:text-white">
                        {{ $invoice->billing_period_start->format('M d, Y') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </p>
                </div>
                @endif

                <!-- Description -->
                @if($invoice->description)
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">{{ __('super_admin.billing.description') }}</h4>
                    <p class="text-gray-900 dark:text-white">{{ $invoice->description }}</p>
                </div>
                @endif

                <!-- Amount Breakdown -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <table class="w-full">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ __('super_admin.billing.subtotal') }}</td>
                                <td class="py-3 text-end text-gray-900 dark:text-white font-medium">
                                    {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->amount ?? 0, 2) }}
                                </td>
                            </tr>
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ __('super_admin.billing.tax') }}</td>
                                <td class="py-3 text-end text-gray-900 dark:text-white">
                                    {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->tax_amount, 2) }}
                                </td>
                            </tr>
                            @endif
                            @if($invoice->discount_amount > 0)
                            <tr>
                                <td class="py-3 text-gray-600 dark:text-gray-400">{{ __('super_admin.billing.discount') }}</td>
                                <td class="py-3 text-end text-green-600">
                                    -{{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->discount_amount, 2) }}
                                </td>
                            </tr>
                            @endif
                            <tr class="text-lg font-bold">
                                <td class="py-4 text-gray-900 dark:text-white">{{ __('super_admin.billing.total') }}</td>
                                <td class="py-4 text-end text-gray-900 dark:text-white">
                                    {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_amount ?? $invoice->amount ?? 0, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            @if($invoice->payments && $invoice->payments->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.billing.payment_history') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($invoice->payments as $payment)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                {{ $payment->status === 'completed' ? 'bg-green-100 dark:bg-green-900/30' :
                                   ($payment->status === 'refunded' ? 'bg-purple-100 dark:bg-purple-900/30' : 'bg-gray-100 dark:bg-gray-700') }}">
                                <i class="fas {{ $payment->status === 'completed' ? 'fa-check' :
                                               ($payment->status === 'refunded' ? 'fa-undo' : 'fa-clock') }}
                                    {{ $payment->status === 'completed' ? 'text-green-600' :
                                       ($payment->status === 'refunded' ? 'text-purple-600' : 'text-gray-600') }}"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $invoice->currency ?? 'USD' }} {{ number_format($payment->amount, 2) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ ucfirst($payment->payment_method ?? 'N/A') }} - {{ $payment->paid_at?->format('M d, Y H:i') ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <span class="text-sm px-2 py-1 rounded-full
                            {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' :
                               ($payment->status === 'refunded' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.quick_info') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.billing.status') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('super_admin.billing.' . $invoice->status) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.billing.currency') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->currency ?? 'USD' }}</span>
                    </div>
                    @if($invoice->paid_at)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.billing.paid_at') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->paid_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                    @if($invoice->payment_method)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.billing.payment_method') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($invoice->payment_method) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Subscription Info -->
            @if($invoice->subscription)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.subscription_info') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.nav.plans') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->subscription->plan->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">{{ __('super_admin.billing.status') }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($invoice->subscription->status ?? 'N/A') }}</span>
                    </div>
                    <a href="{{ route('super-admin.subscriptions.show', $invoice->subscription_id) }}"
                       class="block mt-4 text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('super_admin.billing.view_subscription') }}
                    </a>
                </div>
            </div>
            @endif

            <!-- Organization Info -->
            @if($invoice->org)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.organization_info') }}</h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-lg font-bold text-red-600">
                        {{ substr($invoice->org->name, 0, 2) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $invoice->org->name }}</p>
                        <p class="text-sm text-gray-500">{{ $invoice->org->email ?? '' }}</p>
                    </div>
                </div>
                <a href="{{ route('super-admin.orgs.show', $invoice->org_id) }}"
                   class="block text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    {{ __('super_admin.billing.view_organization') }}
                </a>
            </div>
            @endif
        </div>
    </div>

    <!-- Mark as Paid Modal -->
    <div x-show="showPaymentModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="showPaymentModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.mark_as_paid') }}</h3>
            <form @submit.prevent="submitPayment">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('super_admin.billing.payment_method') }}
                        </label>
                        <select x-model="paymentForm.payment_method" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                            <option value="bank_transfer">{{ __('super_admin.billing.bank_transfer') }}</option>
                            <option value="credit_card">{{ __('super_admin.billing.credit_card') }}</option>
                            <option value="cash">{{ __('super_admin.billing.cash') }}</option>
                            <option value="other">{{ __('super_admin.billing.other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('super_admin.billing.payment_reference') }}
                        </label>
                        <input type="text" x-model="paymentForm.payment_reference"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showPaymentModal = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        {{ __('super_admin.billing.confirm_payment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function invoiceDetails() {
    return {
        showPaymentModal: false,
        paymentForm: {
            payment_method: 'bank_transfer',
            payment_reference: ''
        },

        markAsPaid() {
            this.showPaymentModal = true;
        },

        async submitPayment() {
            try {
                const response = await fetch(`/super-admin/billing/invoices/{{ $invoice->invoice_id }}/mark-paid`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.paymentForm)
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __('common.error') }}');
                }
            } catch (e) {
                alert('{{ __('common.error') }}');
            }
        },

        async sendReminder() {
            if (!confirm('{{ __('super_admin.billing.confirm_send_reminder') }}')) return;

            try {
                const response = await fetch(`/super-admin/billing/invoices/{{ $invoice->invoice_id }}/reminder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                alert(data.message || (data.success ? '{{ __('super_admin.billing.reminder_sent') }}' : '{{ __('common.error') }}'));
            } catch (e) {
                alert('{{ __('common.error') }}');
            }
        },

        async cancelInvoice() {
            if (!confirm('{{ __('super_admin.billing.confirm_cancel') }}')) return;

            try {
                const response = await fetch(`/super-admin/billing/invoices/{{ $invoice->invoice_id }}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __('common.error') }}');
                }
            } catch (e) {
                alert('{{ __('common.error') }}');
            }
        }
    };
}
</script>
@endpush

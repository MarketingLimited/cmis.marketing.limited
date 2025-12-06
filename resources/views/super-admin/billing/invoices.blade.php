@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.invoices'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.invoices') }}</span>
@endsection

@section('content')
<div x-data="invoicesList()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.billing.invoices') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.billing.invoices_description') }}</p>
        </div>
        <a href="{{ route('super-admin.billing.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-plus"></i>
            <span>{{ __('super_admin.billing.create_invoice') }}</span>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.total') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.pending') }}</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.paid') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.overdue') }}</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['overdue'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form action="{{ route('super-admin.billing.invoices') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.billing.search_placeholder') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.billing.all_statuses') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('super_admin.billing.pending') }}</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('super_admin.billing.paid') }}</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('super_admin.billing.overdue') }}</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('super_admin.billing.cancelled') }}</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-search"></i>
            </button>
            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
            <a href="{{ route('super-admin.billing.invoices') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                {{ __('common.clear') }}
            </a>
            @endif
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.invoice_number') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.organization') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.amount') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.status') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.due_date') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.created') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('super-admin.billing.show', $invoice->invoice_id) }}" class="text-sm font-medium text-red-600 hover:text-red-700">
                                {{ $invoice->invoice_number ?? 'INV-' . substr($invoice->invoice_id, 0, 8) }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-bold">
                                    {{ substr($invoice->org->name ?? '?', 0, 2) }}
                                </div>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $invoice->org->name ?? __('common.unknown') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $invoice->currency ?? 'USD' }} {{ number_format($invoice->total_amount ?? $invoice->amount, 2) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
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
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ __('super_admin.billing.' . $invoice->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $invoice->due_date?->format('M d, Y') ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $invoice->created_at->format('M d, Y') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('super-admin.billing.show', $invoice->invoice_id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                   title="{{ __('common.view') }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(!$invoice->isPaid())
                                <button @click="markAsPaid('{{ $invoice->invoice_id }}')"
                                        class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition"
                                        title="{{ __('super_admin.billing.mark_as_paid') }}">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button @click="sendReminder('{{ $invoice->invoice_id }}')"
                                        class="p-2 text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition"
                                        title="{{ __('super_admin.billing.send_reminder') }}">
                                    <i class="fas fa-bell"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <i class="fas fa-file-invoice text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.no_invoices') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $invoices->links() }}
        </div>
        @endif
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
function invoicesList() {
    return {
        showPaymentModal: false,
        selectedInvoiceId: null,
        paymentForm: {
            payment_method: 'bank_transfer',
            payment_reference: ''
        },

        markAsPaid(invoiceId) {
            this.selectedInvoiceId = invoiceId;
            this.showPaymentModal = true;
        },

        async submitPayment() {
            try {
                const response = await fetch(`/super-admin/billing/invoices/${this.selectedInvoiceId}/mark-paid`, {
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

        async sendReminder(invoiceId) {
            if (!confirm('{{ __('super_admin.billing.confirm_send_reminder') }}')) return;

            try {
                const response = await fetch(`/super-admin/billing/invoices/${invoiceId}/reminder`, {
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
        }
    };
}
</script>
@endpush

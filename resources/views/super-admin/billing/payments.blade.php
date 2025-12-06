@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.payments'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.payments') }}</span>
@endsection

@section('content')
<div x-data="paymentsList()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.billing.payments') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.billing.payments_description') }}</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.total_payments') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_payments'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.completed') }}</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.pending') }}</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.failed') }}</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['failed'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.total_amount') }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($stats['total_amount'], 2) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <form action="{{ route('super-admin.billing.payments') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.billing.search_payments') }}"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.billing.all_statuses') }}</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('super_admin.billing.completed') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('super_admin.billing.pending') }}</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('super_admin.billing.failed') }}</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>{{ __('super_admin.billing.refunded') }}</option>
            </select>
            <select name="payment_method" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                <option value="">{{ __('super_admin.billing.all_methods') }}</option>
                <option value="credit_card" {{ request('payment_method') === 'credit_card' ? 'selected' : '' }}>{{ __('super_admin.billing.credit_card') }}</option>
                <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>{{ __('super_admin.billing.bank_transfer') }}</option>
                <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>{{ __('super_admin.billing.cash') }}</option>
                <option value="manual" {{ request('payment_method') === 'manual' ? 'selected' : '' }}>{{ __('super_admin.billing.manual') }}</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.transaction_id') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.organization') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.amount') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.method') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.status') }}
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('super_admin.billing.date') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <span class="text-sm font-mono text-gray-900 dark:text-white">
                                {{ $payment->transaction_id ?? substr($payment->payment_id, 0, 12) . '...' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center text-xs font-bold">
                                    {{ substr($payment->org->name ?? '?', 0, 2) }}
                                </div>
                                <span class="text-sm text-gray-900 dark:text-white">{{ $payment->org->name ?? __('common.unknown') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $payment->currency ?? 'USD' }} {{ number_format($payment->amount, 2) }}
                            </span>
                            @if($payment->refund_amount)
                            <span class="block text-xs text-red-600">
                                {{ __('super_admin.billing.refunded') }}: {{ $payment->currency ?? 'USD' }} {{ number_format($payment->refund_amount, 2) }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ __(sprintf('super_admin.billing.%s', $payment->payment_method ?? 'unknown')) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'refunded' => 'bg-purple-100 text-purple-800',
                                ];
                                $statusClass = $statusClasses[$payment->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ __('super_admin.billing.' . $payment->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $payment->paid_at?->format('M d, Y H:i') ?? $payment->created_at->format('M d, Y H:i') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                @if($payment->invoice_id)
                                <a href="{{ route('super-admin.billing.show', $payment->invoice_id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                   title="{{ __('super_admin.billing.view_invoice') }}">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                @endif
                                @if($payment->status === 'completed' && !$payment->refund_amount)
                                <button @click="showRefundModal('{{ $payment->payment_id }}', {{ $payment->amount }})"
                                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                        title="{{ __('super_admin.billing.refund') }}">
                                    <i class="fas fa-undo"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <i class="fas fa-credit-card text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                            <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.no_payments') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

    <!-- Refund Modal -->
    <div x-show="showRefund" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="showRefund = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.process_refund') }}</h3>
            <form @submit.prevent="submitRefund">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('super_admin.billing.refund_amount') }}
                        </label>
                        <div class="flex items-center gap-2">
                            <span class="text-gray-500">$</span>
                            <input type="number" x-model="refundForm.amount" step="0.01" :max="maxRefund"
                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ __('super_admin.billing.max') }}: $<span x-text="maxRefund"></span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('super_admin.billing.refund_reason') }}
                        </label>
                        <textarea x-model="refundForm.reason" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showRefund = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        {{ __('super_admin.billing.process_refund') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function paymentsList() {
    return {
        showRefund: false,
        selectedPaymentId: null,
        maxRefund: 0,
        refundForm: {
            amount: 0,
            reason: ''
        },

        showRefundModal(paymentId, amount) {
            this.selectedPaymentId = paymentId;
            this.maxRefund = amount;
            this.refundForm.amount = amount;
            this.refundForm.reason = '';
            this.showRefund = true;
        },

        async submitRefund() {
            if (!confirm('{{ __('super_admin.billing.confirm_refund') }}')) return;

            try {
                const response = await fetch(`/super-admin/billing/payments/${this.selectedPaymentId}/refund`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.refundForm)
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

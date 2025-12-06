@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.title'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.title') }}</span>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div x-data="billingDashboard()" x-init="init()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.billing.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.billing.description') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('super-admin.billing.invoices') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <i class="fas fa-file-invoice"></i>
                <span>{{ __('super_admin.billing.all_invoices') }}</span>
            </a>
            <a href="{{ route('super-admin.billing.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-plus"></i>
                <span>{{ __('super_admin.billing.create_invoice') }}</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.total_revenue') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ${{ number_format($stats['total_revenue'], 2) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- This Month Revenue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.this_month') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ${{ number_format($stats['current_month_revenue'], 2) }}
                    </p>
                    @if($stats['growth_percentage'] != 0)
                    <p class="text-xs mt-1 {{ $stats['growth_percentage'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        <i class="fas fa-{{ $stats['growth_percentage'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                        {{ abs($stats['growth_percentage']) }}% {{ __('super_admin.billing.vs_last_month') }}
                    </p>
                    @endif
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.pending_invoices') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $stats['pending_invoices'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Overdue Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.overdue_invoices') }}</p>
                    <p class="text-2xl font-bold {{ $stats['overdue_invoices'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }} mt-1">
                        {{ $stats['overdue_invoices'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Monthly Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('super_admin.billing.revenue_trend') }}
            </h3>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Revenue by Plan -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('super_admin.billing.revenue_by_plan') }}
            </h3>
            <div class="h-64">
                <canvas id="planRevenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('super_admin.billing.recent_invoices') }}
                </h3>
                <a href="{{ route('super-admin.billing.invoices') }}" class="text-sm text-red-600 hover:text-red-700">
                    {{ __('common.view_all') }}
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentInvoices as $invoice)
                <a href="{{ route('super-admin.billing.show', $invoice->invoice_id) }}" class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center
                            {{ $invoice->status === 'paid' ? 'bg-green-100 dark:bg-green-900/30' :
                               ($invoice->status === 'overdue' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30') }}">
                            <i class="fas {{ $invoice->status === 'paid' ? 'fa-check' :
                                           ($invoice->status === 'overdue' ? 'fa-exclamation' : 'fa-clock') }}
                                {{ $invoice->status === 'paid' ? 'text-green-600 dark:text-green-400' :
                                   ($invoice->status === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400') }}"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $invoice->invoice_number ?? 'INV-' . substr($invoice->invoice_id, 0, 8) }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $invoice->org->name ?? __('common.unknown') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            ${{ number_format($invoice->total_amount ?? $invoice->amount, 2) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $invoice->created_at->format('M d, Y') }}</p>
                    </div>
                </a>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-file-invoice text-4xl mb-2 opacity-50"></i>
                    <p>{{ __('super_admin.billing.no_invoices') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Overdue Invoices -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('super_admin.billing.overdue_list') }}
                </h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($overdueInvoices as $invoice)
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $invoice->org->name ?? __('common.unknown') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ __('super_admin.billing.due') }}: {{ $invoice->due_date?->format('M d, Y') ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-red-600">
                            ${{ number_format($invoice->total_amount ?? $invoice->amount, 2) }}
                        </span>
                        <button @click="sendReminder('{{ $invoice->invoice_id }}')"
                                class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                            {{ __('super_admin.billing.send_reminder') }}
                        </button>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-green-600 dark:text-green-400">
                    <i class="fas fa-check-circle text-4xl mb-2"></i>
                    <p>{{ __('super_admin.billing.no_overdue') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('super-admin.billing.revenue') }}" class="flex items-center gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-pie text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.billing.revenue_analytics') }}</h4>
                <p class="text-sm text-gray-500">{{ __('super_admin.billing.revenue_analytics_desc') }}</p>
            </div>
        </a>

        <a href="{{ route('super-admin.billing.payments') }}" class="flex items-center gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-credit-card text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.billing.payment_history') }}</h4>
                <p class="text-sm text-gray-500">{{ __('super_admin.billing.payment_history_desc') }}</p>
            </div>
        </a>

        <a href="{{ route('super-admin.subscriptions.index') }}" class="flex items-center gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:shadow-md transition">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <i class="fas fa-sync-alt text-green-600 dark:text-green-400 text-xl"></i>
            </div>
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.nav.subscriptions') }}</h4>
                <p class="text-sm text-gray-500">{{ __('super_admin.billing.subscriptions_desc') }}</p>
            </div>
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function billingDashboard() {
    return {
        revenueChart: null,
        planChart: null,

        init() {
            this.$nextTick(() => {
                this.initRevenueChart();
                this.initPlanChart();
            });
        },

        initRevenueChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            const monthlyData = @json($monthlyRevenue);

            this.revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(d => d.month),
                    datasets: [{
                        label: '{{ __('super_admin.billing.revenue') }}',
                        data: monthlyData.map(d => d.revenue),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '$' + value.toLocaleString()
                            }
                        }
                    }
                }
            });
        },

        initPlanChart() {
            const ctx = document.getElementById('planRevenueChart');
            if (!ctx) return;

            const planData = @json($revenueByPlan);

            this.planChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: planData.map(d => d.name),
                    datasets: [{
                        data: planData.map(d => d.revenue),
                        backgroundColor: [
                            '#dc2626',
                            '#2563eb',
                            '#16a34a',
                            '#ca8a04',
                            '#9333ea'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: window.isRtl ? 'left' : 'right'
                        }
                    }
                }
            });
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

                if (data.success) {
                    alert('{{ __('super_admin.billing.reminder_sent') }}');
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

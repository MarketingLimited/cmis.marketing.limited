@extends('super-admin.layouts.app')

@section('title', __('super_admin.billing.revenue_analytics'))

@section('breadcrumb')
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <a href="{{ route('super-admin.billing.index') }}" class="text-gray-500 hover:text-red-600 transition">
        {{ __('super_admin.billing.title') }}
    </a>
    <i class="fas fa-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }} text-gray-400 text-xs"></i>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.billing.revenue_analytics') }}</span>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div x-data="revenueAnalytics()" x-init="init()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.billing.revenue_analytics') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('super_admin.billing.revenue_analytics_desc') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.billing.revenue', ['period' => 'month']) }}"
               class="px-4 py-2 rounded-lg transition {{ $period === 'month' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('super_admin.billing.this_month') }}
            </a>
            <a href="{{ route('super-admin.billing.revenue', ['period' => 'quarter']) }}"
               class="px-4 py-2 rounded-lg transition {{ $period === 'quarter' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('super_admin.billing.this_quarter') }}
            </a>
            <a href="{{ route('super-admin.billing.revenue', ['period' => 'year']) }}"
               class="px-4 py-2 rounded-lg transition {{ $period === 'year' ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                {{ __('super_admin.billing.this_year') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.invoices_paid') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $stats['invoice_count'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-invoice text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.average_invoice') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        ${{ number_format($stats['average_invoice'], 2) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calculator text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.billing.outstanding') }}</p>
                    <p class="text-2xl font-bold {{ $stats['outstanding_amount'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white' }} mt-1">
                        ${{ number_format($stats['outstanding_amount'], 2) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Over Time -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.revenue_over_time') }}</h3>
            <div class="h-72">
                <canvas id="revenueTimeChart"></canvas>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.billing.payment_methods') }}</h3>
            <div class="h-72">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.billing.top_organizations') }}</h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($revenueByOrg as $index => $org)
                <div class="flex items-center justify-between p-4">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 flex items-center justify-center text-xs font-bold rounded-full
                            {{ $index < 3 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $index + 1 }}
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $org->name }}</span>
                    </div>
                    <span class="text-sm font-semibold text-green-600">${{ number_format($org->revenue, 2) }}</span>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    {{ __('super_admin.billing.no_data') }}
                </div>
                @endforelse
            </div>
        </div>

        <!-- Revenue by Currency -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.billing.revenue_by_currency') }}</h3>
            </div>
            <div class="p-5">
                @if($revenueByCurrency->count() > 0)
                <div class="space-y-4">
                    @foreach($revenueByCurrency as $currency)
                    @php
                        $percentage = $stats['total_revenue'] > 0 ? ($currency->revenue / $stats['total_revenue']) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-900 dark:text-white">{{ $currency->currency ?? 'USD' }}</span>
                            <span class="text-gray-600 dark:text-gray-400">${{ number_format($currency->revenue, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-red-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    {{ __('super_admin.billing.no_data') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function revenueAnalytics() {
    return {
        revenueChart: null,
        methodsChart: null,

        init() {
            this.$nextTick(() => {
                this.initRevenueChart();
                this.initMethodsChart();
            });
        },

        initRevenueChart() {
            const ctx = document.getElementById('revenueTimeChart');
            if (!ctx) return;

            const timeData = @json($revenueOverTime);

            this.revenueChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: timeData.map(d => d.period),
                    datasets: [{
                        label: '{{ __('super_admin.billing.revenue') }}',
                        data: timeData.map(d => d.revenue),
                        backgroundColor: 'rgba(220, 38, 38, 0.8)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4
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

        initMethodsChart() {
            const ctx = document.getElementById('paymentMethodsChart');
            if (!ctx) return;

            const methodsData = @json($paymentMethods);

            if (methodsData.length === 0) {
                ctx.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">{{ __('super_admin.billing.no_data') }}</div>';
                return;
            }

            const labels = methodsData.map(d => {
                const methodLabels = {
                    'credit_card': '{{ __('super_admin.billing.credit_card') }}',
                    'bank_transfer': '{{ __('super_admin.billing.bank_transfer') }}',
                    'cash': '{{ __('super_admin.billing.cash') }}',
                    'manual': '{{ __('super_admin.billing.manual') }}',
                    'other': '{{ __('super_admin.billing.other') }}'
                };
                return methodLabels[d.payment_method] || d.payment_method;
            });

            this.methodsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: methodsData.map(d => d.total),
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
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush

@extends('layouts.admin')

@section('title', __('AI Analytics Dashboard'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div x-data="analyticsDashboard()" x-init="init()" class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('AI Analytics') }}</span>
        </nav>
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('AI Analytics Dashboard') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('Monitor AI usage, costs, and quota status') }}</p>
            </div>
        <div class="flex space-x-3">
            <button @click="refreshData()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    :disabled="loading">
                <span x-show="!loading">Refresh Data</span>
                <span x-show="loading">Loading...</span>
            </button>
            <button @click="exportData()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                Export Report
            </button>
        </div>
    </div>

    <!-- Quota Alerts -->
    <div x-show="alerts.length > 0" class="mb-6">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Quota Alerts</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <template x-for="alert in alerts" :key="alert.type + alert.scope">
                                <li x-text="alert.message"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Requests -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Requests</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(summary.total_requests)">0</p>
                    <p class="text-xs text-gray-500 mt-1" x-text="summary.period"></p>
                </div>
            </div>
        </div>

        <!-- Total Tokens -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Tokens</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(summary.total_tokens)">0</p>
                    <p class="text-xs text-gray-500 mt-1">Across all types</p>
                </div>
            </div>
        </div>

        <!-- Total Cost -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Cost</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="'$' + summary.total_cost.toFixed(2)">$0.00</p>
                    <p class="text-xs text-gray-500 mt-1" x-text="summary.period"></p>
                </div>
            </div>
        </div>

        <!-- Quota Health -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 rounded-lg p-3"
                     :class="{
                         'bg-green-100': quota.health.overall === 'healthy',
                         'bg-yellow-100': quota.health.overall === 'warning',
                         'bg-red-100': quota.health.overall === 'critical'
                     }">
                    <svg class="h-6 w-6"
                         :class="{
                             'text-green-600': quota.health.overall === 'healthy',
                             'text-yellow-600': quota.health.overall === 'warning',
                             'text-red-600': quota.health.overall === 'critical'
                         }"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Quota Health</p>
                    <p class="text-2xl font-bold text-gray-900 capitalize" x-text="quota.health.overall">Healthy</p>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.quota_type + ' plan'">Premium plan</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quota Status Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Text Quota -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Text Generation</h3>
            <div class="space-y-4">
                <!-- Daily -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Daily</span>
                        <span class="font-medium" x-text="quota.text.used_daily + ' / ' + quota.text.daily">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.text.percentage_daily < 75,
                                 'bg-yellow-600': quota.text.percentage_daily >= 75 && quota.text.percentage_daily < 90,
                                 'bg-red-600': quota.text.percentage_daily >= 90
                             }"
                             :style="'width: ' + Math.min(quota.text.percentage_daily, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.text.percentage_daily.toFixed(1) + '% used'">0% used</p>
                </div>
                <!-- Monthly -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Monthly</span>
                        <span class="font-medium" x-text="quota.text.used_monthly + ' / ' + quota.text.monthly">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.text.percentage_monthly < 75,
                                 'bg-yellow-600': quota.text.percentage_monthly >= 75 && quota.text.percentage_monthly < 90,
                                 'bg-red-600': quota.text.percentage_monthly >= 90
                             }"
                             :style="'width: ' + Math.min(quota.text.percentage_monthly, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.text.percentage_monthly.toFixed(1) + '% used'">0% used</p>
                </div>
            </div>
        </div>

        <!-- Image Quota -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Image Generation</h3>
            <div class="space-y-4">
                <!-- Daily -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Daily</span>
                        <span class="font-medium" x-text="quota.image.used_daily + ' / ' + quota.image.daily">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.image.percentage_daily < 75,
                                 'bg-yellow-600': quota.image.percentage_daily >= 75 && quota.image.percentage_daily < 90,
                                 'bg-red-600': quota.image.percentage_daily >= 90
                             }"
                             :style="'width: ' + Math.min(quota.image.percentage_daily, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.image.percentage_daily.toFixed(1) + '% used'">0% used</p>
                </div>
                <!-- Monthly -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Monthly</span>
                        <span class="font-medium" x-text="quota.image.used_monthly + ' / ' + quota.image.monthly">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.image.percentage_monthly < 75,
                                 'bg-yellow-600': quota.image.percentage_monthly >= 75 && quota.image.percentage_monthly < 90,
                                 'bg-red-600': quota.image.percentage_monthly >= 90
                             }"
                             :style="'width: ' + Math.min(quota.image.percentage_monthly, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.image.percentage_monthly.toFixed(1) + '% used'">0% used</p>
                </div>
            </div>
        </div>

        <!-- Video Quota -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Video Generation</h3>
            <div class="space-y-4">
                <!-- Daily -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Daily</span>
                        <span class="font-medium" x-text="quota.video.used_daily + ' / ' + quota.video.daily">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.video.percentage_daily < 75,
                                 'bg-yellow-600': quota.video.percentage_daily >= 75 && quota.video.percentage_daily < 90,
                                 'bg-red-600': quota.video.percentage_daily >= 90
                             }"
                             :style="'width: ' + Math.min(quota.video.percentage_daily, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.video.percentage_daily.toFixed(1) + '% used'">0% used</p>
                </div>
                <!-- Monthly -->
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Monthly</span>
                        <span class="font-medium" x-text="quota.video.used_monthly + ' / ' + quota.video.monthly">0 / 0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all"
                             :class="{
                                 'bg-green-600': quota.video.percentage_monthly < 75,
                                 'bg-yellow-600': quota.video.percentage_monthly >= 75 && quota.video.percentage_monthly < 90,
                                 'bg-red-600': quota.video.percentage_monthly >= 90
                             }"
                             :style="'width: ' + Math.min(quota.video.percentage_monthly, 100) + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1" x-text="quota.video.percentage_monthly.toFixed(1) + '% used'">0% used</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Daily Trend Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Usage Trend (30 Days)</h3>
            <canvas id="dailyTrendChart" height="250"></canvas>
        </div>

        <!-- Monthly Cost Comparison -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Cost Comparison (6 Months)</h3>
            <canvas id="monthlyCostChart" height="250"></canvas>
        </div>
    </div>

    <!-- Usage by Type -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage by Type</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tokens</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Cost</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="type in usageByType" :key="type.type">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                      :class="{
                                          'bg-blue-100 text-blue-800': type.type === 'text',
                                          'bg-green-100 text-green-800': type.type === 'image',
                                          'bg-purple-100 text-purple-800': type.type === 'video'
                                      }"
                                      x-text="type.type">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatNumber(type.count)">0</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatNumber(type.tokens)">0</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="'$' + type.cost.toFixed(2)">$0.00</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="'$' + (type.cost / type.count).toFixed(4)">$0.00</td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function analyticsDashboard() {
    return {
        loading: false,
        summary: {
            total_requests: 0,
            total_tokens: 0,
            total_cost: 0,
            period: 'Last 30 days'
        },
        quota: {
            quota_type: 'free',
            text: { daily: 0, monthly: 0, used_daily: 0, used_monthly: 0, percentage_daily: 0, percentage_monthly: 0 },
            image: { daily: 0, monthly: 0, used_daily: 0, used_monthly: 0, percentage_daily: 0, percentage_monthly: 0 },
            video: { daily: 0, monthly: 0, used_daily: 0, used_monthly: 0, percentage_daily: 0, percentage_monthly: 0 },
            health: { overall: 'healthy' }
        },
        alerts: [],
        usageByType: [],
        dailyTrendChart: null,
        monthlyCostChart: null,

        async init() {
            await this.loadDashboard();
            await this.loadAlerts();
        },

        async loadDashboard() {
            this.loading = true;
            try {
                const orgId = document.querySelector('[x-data]').dataset.orgId;
                const token = document.querySelector('meta[name="api-token"]').content;

                const response = await fetch(`/api/orgs/${orgId}/analytics/ai/dashboard`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to load dashboard');

                const data = await response.json();

                // Update summary
                this.summary = {
                    total_requests: data.dashboard.summary.summary.total_requests,
                    total_tokens: data.dashboard.summary.summary.total_tokens,
                    total_cost: data.dashboard.summary.summary.total_cost,
                    period: `${data.dashboard.summary.period.days} days`
                };

                // Update quota
                this.quota = data.dashboard.quota;

                // Update usage by type
                this.usageByType = data.dashboard.summary.by_type;

                // Render charts
                this.renderDailyTrendChart(data.dashboard.daily_trend);
                this.renderMonthlyCostChart(data.dashboard.monthly_comparison);

            } catch (error) {
                console.error('Error loading dashboard:', error);
                alert('Failed to load dashboard data');
            } finally {
                this.loading = false;
            }
        },

        async loadAlerts() {
            try {
                const orgId = document.querySelector('[x-data]').dataset.orgId;
                const token = document.querySelector('meta[name="api-token"]').content;

                const response = await fetch(`/api/orgs/${orgId}/analytics/ai/quota-alerts`, {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) return;

                const data = await response.json();
                this.alerts = data.alerts;

            } catch (error) {
                console.error('Error loading alerts:', error);
            }
        },

        renderDailyTrendChart(data) {
            const ctx = document.getElementById('dailyTrendChart');

            if (this.dailyTrendChart) {
                this.dailyTrendChart.destroy();
            }

            this.dailyTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.date),
                    datasets: [
                        {
                            label: 'Requests',
                            data: data.map(d => d.requests),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Cost ($)',
                            data: data.map(d => d.cost),
                            borderColor: 'rgb(234, 179, 8)',
                            backgroundColor: 'rgba(234, 179, 8, 0.1)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Requests'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Cost ($)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        },

        renderMonthlyCostChart(data) {
            const ctx = document.getElementById('monthlyCostChart');

            if (this.monthlyCostChart) {
                this.monthlyCostChart.destroy();
            }

            this.monthlyCostChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month_name),
                    datasets: [{
                        label: 'Monthly Cost ($)',
                        data: data.map(d => d.cost),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cost ($)'
                            }
                        }
                    }
                }
            });
        },

        async refreshData() {
            await this.loadDashboard();
            await this.loadAlerts();
        },

        async exportData() {
            try {
                const orgId = document.querySelector('[x-data]').dataset.orgId;
                const token = document.querySelector('meta[name="api-token"]').content;

                const response = await fetch(`/api/orgs/${orgId}/analytics/ai/export`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type: 'usage' })
                });

                if (!response.ok) throw new Error('Export failed');

                const data = await response.json();

                // Convert to CSV and download
                const csv = this.convertToCSV(data.data);
                this.downloadCSV(csv, 'analytics-export.csv');

            } catch (error) {
                console.error('Error exporting data:', error);
                alert('Failed to export data');
            }
        },

        convertToCSV(data) {
            // Implementation for CSV conversion
            return 'CSV data here';
        },

        downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
        },

        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        }
    }
}
</script>
@endpush
@endsection

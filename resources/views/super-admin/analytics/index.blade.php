@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.analytics.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.analytics.title') }}</span>
@endsection

@section('content')
<div x-data="analyticsManager()" x-init="loadAnalytics()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.analytics.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.analytics.subtitle') }}</p>
        </div>

        <!-- Time Range Selector -->
        <div class="flex items-center gap-2">
            <template x-for="range in timeRanges" :key="range.value">
                <button @click="selectedRange = range.value; loadAnalytics()"
                        :class="selectedRange === range.value ? 'bg-red-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition"
                        x-text="range.label"></button>
            </template>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.analytics.total_requests') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.totalRequests)">0</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-server text-xl text-blue-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1 text-sm">
                <span :class="stats.requestsChange >= 0 ? 'text-green-600' : 'text-red-600'">
                    <i class="fas" :class="stats.requestsChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'"></i>
                    <span x-text="Math.abs(stats.requestsChange) + '%'"></span>
                </span>
                <span class="text-gray-500">{{ __('super_admin.analytics.vs_previous') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.analytics.error_rate') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><span x-text="stats.errorRate">0</span>%</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-xl text-red-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1 text-sm">
                <span class="text-gray-500"><span x-text="formatNumber(stats.totalErrors)"></span> {{ __('super_admin.analytics.errors') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.analytics.avg_response_time') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white"><span x-text="stats.avgResponseTime">0</span>ms</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-tachometer-alt text-xl text-green-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1 text-sm">
                <span class="text-gray-500">p95: <span x-text="stats.p95ResponseTime">0</span>ms</span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('super_admin.analytics.rate_limit_hits') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(stats.rateLimitHits)">0</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <i class="fas fa-hand-paper text-xl text-yellow-600"></i>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-1 text-sm">
                <span class="text-gray-500"><span x-text="stats.uniqueOrgsLimited">0</span> {{ __('super_admin.analytics.orgs_affected') }}</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Requests Over Time Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.analytics.requests_over_time') }}</h3>
            <div class="h-64">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>

        <!-- Requests by Platform Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('super_admin.analytics.by_platform') }}</h3>
            <div class="h-64">
                <canvas id="platformChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.analytics.top_organizations') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.organization') }}
                            </th>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.requests') }}
                            </th>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.error_rate') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="org in topOrganizations" :key="org.org_id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    <a :href="'{{ url('super-admin/organizations') }}/' + org.org_id"
                                       class="font-medium text-gray-900 dark:text-white hover:text-red-600"
                                       x-text="org.name"></a>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="formatNumber(org.requests)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="org.error_rate > 5 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'"
                                          x-text="org.error_rate + '%'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Endpoints -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.analytics.top_endpoints') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.endpoint') }}
                            </th>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.requests') }}
                            </th>
                            <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                {{ __('super_admin.analytics.avg_time') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="endpoint in topEndpoints" :key="endpoint.path">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="px-1.5 py-0.5 text-xs font-medium rounded"
                                              :class="{
                                                  'bg-green-100 text-green-800': endpoint.method === 'GET',
                                                  'bg-blue-100 text-blue-800': endpoint.method === 'POST',
                                                  'bg-yellow-100 text-yellow-800': endpoint.method === 'PUT' || endpoint.method === 'PATCH',
                                                  'bg-red-100 text-red-800': endpoint.method === 'DELETE'
                                              }"
                                              x-text="endpoint.method"></span>
                                        <span class="font-mono text-sm text-gray-900 dark:text-white" x-text="endpoint.path"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="formatNumber(endpoint.requests)"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="endpoint.avg_time + 'ms'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Error Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.analytics.recent_errors') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.analytics.time') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.analytics.status') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.analytics.endpoint') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.analytics.organization') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.analytics.message') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="error in recentErrors" :key="error.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400" x-text="formatTime(error.created_at)"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                      :class="{
                                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': error.status >= 400 && error.status < 500,
                                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': error.status >= 500
                                      }"
                                      x-text="error.status"></span>
                            </td>
                            <td class="px-4 py-3 font-mono text-sm text-gray-900 dark:text-white" x-text="error.endpoint"></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="error.org_name || '-'"></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs" x-text="error.message"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div x-show="recentErrors.length === 0" class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
            <p>{{ __('super_admin.analytics.no_errors') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function analyticsManager() {
    return {
        loading: true,
        selectedRange: '24h',
        timeRanges: [
            { value: '1h', label: '{{ __('super_admin.analytics.1h') }}' },
            { value: '6h', label: '{{ __('super_admin.analytics.6h') }}' },
            { value: '24h', label: '{{ __('super_admin.analytics.24h') }}' },
            { value: '7d', label: '{{ __('super_admin.analytics.7d') }}' },
            { value: '30d', label: '{{ __('super_admin.analytics.30d') }}' }
        ],
        stats: {
            totalRequests: 0,
            requestsChange: 0,
            errorRate: 0,
            totalErrors: 0,
            avgResponseTime: 0,
            p95ResponseTime: 0,
            rateLimitHits: 0,
            uniqueOrgsLimited: 0
        },
        topOrganizations: [],
        topEndpoints: [],
        recentErrors: [],
        requestsChart: null,
        platformChart: null,

        async loadAnalytics() {
            this.loading = true;
            try {
                const response = await fetch(`{{ route('super-admin.analytics.index') }}?range=${this.selectedRange}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                // API returns: { success, data: { overview, by_platform, hourly_stats } }
                const data = result.data || result;
                const overview = data.overview || {};

                // Map overview data to stats
                this.stats = {
                    totalRequests: overview.total_requests || 0,
                    requestsChange: 0, // Not provided by API
                    errorRate: overview.error_rate || 0,
                    totalErrors: overview.failed_requests || 0,
                    avgResponseTime: Math.round(overview.avg_response_time || 0),
                    p95ResponseTime: 0, // Not provided by API
                    rateLimitHits: 0, // Not provided by API
                    uniqueOrgsLimited: 0 // Not provided by API
                };

                // Load additional data for tables
                await this.loadTopOrganizations();
                await this.loadTopEndpoints();
                await this.loadRecentErrors();

                // Prepare chart data
                const hourlyStats = data.hourly_stats || [];
                const byPlatform = data.by_platform || {};
                this.renderCharts({
                    requestsLabels: hourlyStats.map(h => h.hour || ''),
                    requestsData: hourlyStats.map(h => h.total || 0),
                    platformLabels: Object.keys(byPlatform),
                    platformData: Object.values(byPlatform)
                });
            } catch (error) {
                console.error('Error loading analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadTopOrganizations() {
            try {
                const response = await fetch(`{{ route('super-admin.analytics.by-org') }}?range=${this.selectedRange}&limit=10`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                const orgs = result.data || [];
                this.topOrganizations = orgs.map(org => ({
                    org_id: org.org_id,
                    name: org.name,
                    requests: org.total_calls || 0,
                    error_rate: org.failed && org.total_calls ? Math.round((org.failed / org.total_calls) * 100) : 0
                }));
            } catch (error) {
                console.error('Error loading top organizations:', error);
            }
        },

        async loadTopEndpoints() {
            try {
                const response = await fetch(`{{ route('super-admin.analytics.endpoints') }}?range=${this.selectedRange}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                const endpoints = result.data || [];
                this.topEndpoints = endpoints.slice(0, 10).map(ep => ({
                    method: 'GET', // API doesn't return method
                    path: ep.endpoint || '',
                    requests: ep.total_calls || 0,
                    avg_time: Math.round(ep.avg_duration || 0)
                }));
            } catch (error) {
                console.error('Error loading top endpoints:', error);
            }
        },

        async loadRecentErrors() {
            try {
                const response = await fetch(`{{ route('super-admin.analytics.errors') }}?range=${this.selectedRange}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json();
                const data = result.data || {};
                const errors = data.recent_errors || [];
                this.recentErrors = errors.slice(0, 10).map(err => ({
                    id: err.call_id,
                    created_at: err.called_at,
                    status: err.http_status || 500,
                    endpoint: err.endpoint || '',
                    org_name: '', // Not provided
                    message: err.error_message || 'Unknown error'
                }));
            } catch (error) {
                console.error('Error loading recent errors:', error);
            }
        },

        renderCharts(chartData) {
            // Requests over time chart
            const requestsCtx = document.getElementById('requestsChart');
            if (requestsCtx) {
                if (this.requestsChart) {
                    this.requestsChart.destroy();
                }

                this.requestsChart = new Chart(requestsCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.requestsLabels || [],
                        datasets: [{
                            label: '{{ __('super_admin.analytics.requests') }}',
                            data: chartData.requestsData || [],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Platform chart
            const platformCtx = document.getElementById('platformChart');
            if (platformCtx) {
                if (this.platformChart) {
                    this.platformChart.destroy();
                }

                this.platformChart = new Chart(platformCtx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.platformLabels || ['Meta', 'Google', 'TikTok', 'Twitter', 'LinkedIn'],
                        datasets: [{
                            data: chartData.platformData || [30, 25, 20, 15, 10],
                            backgroundColor: [
                                '#1877f2',
                                '#ea4335',
                                '#000000',
                                '#1da1f2',
                                '#0077b5'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: '{{ $isRtl ? 'left' : 'right' }}'
                            }
                        }
                    }
                });
            }
        },

        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            }
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num?.toString() || '0';
        },

        formatTime(timestamp) {
            return new Date(timestamp).toLocaleString();
        }
    };
}
</script>
@endpush

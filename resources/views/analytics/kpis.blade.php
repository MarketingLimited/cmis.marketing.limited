@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('KPI Dashboard'))

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.analytics.enterprise', ['org' => $currentOrg]) }}" class="hover:text-blue-600 transition">
                {{ __('Analytics') }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('KPIs') }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('KPI Performance Dashboard') }}</h1>
                <p class="mt-1 text-gray-600">{{ ($entityName ?? 'Organization') }} - {{ __('Key performance indicators and health metrics') }}</p>
            </div>
            <a href="{{ route('orgs.analytics.enterprise', ['org' => $currentOrg]) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium transition flex items-center gap-2">
                <i class="fas fa-arrow-right"></i>{{ __('Back to Hub') }}
            </a>
        </div>
    </div>

    {{-- KPI Dashboard Component --}}
    <div x-data="kpiDashboard('{{ $orgId }}', '{{ $entityType ?? 'org' }}', '{{ $entityId ?? $orgId }}')" class="space-y-6">

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Campaigns KPI --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 rounded-xl">
                        <i class="fas fa-bullhorn text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full"
                          :class="kpis.campaigns?.trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        <i :class="kpis.campaigns?.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                        <span x-text="Math.abs(kpis.campaigns?.trend || 0) + '%'"></span>
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800" x-text="kpis.campaigns?.value || 0"></h3>
                <p class="text-gray-500 text-sm mt-1">{{ __('Active Campaigns') }}</p>
                <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full" :style="'width: ' + (kpis.campaigns?.progress || 0) + '%'"></div>
                </div>
            </div>

            {{-- Impressions KPI --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <i class="fas fa-eye text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full"
                          :class="kpis.impressions?.trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        <i :class="kpis.impressions?.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                        <span x-text="Math.abs(kpis.impressions?.trend || 0) + '%'"></span>
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800" x-text="formatNumber(kpis.impressions?.value || 0)"></h3>
                <p class="text-gray-500 text-sm mt-1">{{ __('Total Impressions') }}</p>
                <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-500 rounded-full" :style="'width: ' + (kpis.impressions?.progress || 0) + '%'"></div>
                </div>
            </div>

            {{-- CTR KPI --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-100 rounded-xl">
                        <i class="fas fa-mouse-pointer text-green-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full"
                          :class="kpis.ctr?.trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        <i :class="kpis.ctr?.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                        <span x-text="Math.abs(kpis.ctr?.trend || 0) + '%'"></span>
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800" x-text="(kpis.ctr?.value || 0).toFixed(2) + '%'"></h3>
                <p class="text-gray-500 text-sm mt-1">{{ __('Click-Through Rate') }}</p>
                <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500 rounded-full" :style="'width: ' + Math.min((kpis.ctr?.value || 0) * 10, 100) + '%'"></div>
                </div>
            </div>

            {{-- ROAS KPI --}}
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-100 rounded-xl">
                        <i class="fas fa-chart-line text-amber-600 text-xl"></i>
                    </div>
                    <span class="text-xs font-medium px-2 py-1 rounded-full"
                          :class="kpis.roas?.trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                        <i :class="kpis.roas?.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                        <span x-text="Math.abs(kpis.roas?.trend || 0) + '%'"></span>
                    </span>
                </div>
                <h3 class="text-3xl font-bold text-gray-800" x-text="(kpis.roas?.value || 0).toFixed(2) + 'x'"></h3>
                <p class="text-gray-500 text-sm mt-1">{{ __('Return on Ad Spend') }}</p>
                <div class="mt-3 h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-amber-500 rounded-full" :style="'width: ' + Math.min((kpis.roas?.value || 0) * 20, 100) + '%'"></div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Performance Trend Chart --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Performance Trend') }}</h3>
                    <select x-model="chartPeriod" @change="updateCharts()" class="text-sm border border-gray-200 rounded-lg px-3 py-2">
                        <option value="7d">{{ __('Last 7 Days') }}</option>
                        <option value="30d">{{ __('Last 30 Days') }}</option>
                        <option value="90d">{{ __('Last 90 Days') }}</option>
                    </select>
                </div>
                <div class="h-64">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            {{-- KPI Health Gauge --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-6">{{ __('KPI Health Score') }}</h3>
                <div class="flex items-center justify-center h-64">
                    <div class="relative w-48 h-48">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#e5e7eb" stroke-width="10"/>
                            <circle cx="50" cy="50" r="45" fill="none" stroke="url(#gradient)" stroke-width="10"
                                    :stroke-dasharray="2 * Math.PI * 45"
                                    :stroke-dashoffset="2 * Math.PI * 45 * (1 - healthScore / 100)"
                                    stroke-linecap="round"/>
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#10b981"/>
                                    <stop offset="50%" stop-color="#3b82f6"/>
                                    <stop offset="100%" stop-color="#8b5cf6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-bold text-gray-800" x-text="healthScore"></span>
                            <span class="text-sm text-gray-500">{{ __('Health Score') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Details Table --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('KPI Details') }}</h3>
                <button @click="refreshData()" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                    {{ __('Refresh') }}
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('KPI Name') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('Current') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('Target') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('Progress') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('Trend') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="kpi in kpiList" :key="kpi.name">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg" :class="kpi.iconBg">
                                            <i :class="kpi.icon + ' ' + kpi.iconColor"></i>
                                        </div>
                                        <span class="font-medium text-gray-800" x-text="kpi.name"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-800" x-text="kpi.current"></td>
                                <td class="px-6 py-4 text-gray-600" x-text="kpi.target"></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500"
                                                 :class="kpi.progress >= 100 ? 'bg-green-500' : kpi.progress >= 70 ? 'bg-blue-500' : 'bg-amber-500'"
                                                 :style="'width: ' + Math.min(kpi.progress, 100) + '%'"></div>
                                        </div>
                                        <span class="text-sm text-gray-600" x-text="kpi.progress + '%'"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center gap-1"
                                          :class="kpi.trend >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <i :class="kpi.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                                        <span x-text="Math.abs(kpi.trend) + '%'"></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700': kpi.status === 'on_track',
                                              'bg-amber-100 text-amber-700': kpi.status === 'at_risk',
                                              'bg-red-100 text-red-700': kpi.status === 'off_track'
                                          }"
                                          x-text="kpi.statusLabel"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div x-show="loading" x-cloak class="fixed inset-0 bg-black/20 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 shadow-2xl flex items-center gap-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-700">{{ __('Loading KPI data...') }}</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function kpiDashboard(orgId, entityType, entityId) {
    return {
        orgId: orgId,
        entityType: entityType,
        entityId: entityId,
        loading: false,
        chartPeriod: '30d',
        healthScore: 78,
        performanceChart: null,

        kpis: {
            campaigns: { value: 12, trend: 8.5, progress: 75 },
            impressions: { value: 1250000, trend: 12.3, progress: 85 },
            ctr: { value: 3.45, trend: -2.1, progress: 69 },
            roas: { value: 4.2, trend: 15.7, progress: 84 }
        },

        kpiList: [
            { name: '{{ __("Active Campaigns") }}', current: '12', target: '15', progress: 80, trend: 8.5, status: 'on_track', statusLabel: '{{ __("On Track") }}', icon: 'fas fa-bullhorn', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
            { name: '{{ __("Total Impressions") }}', current: '1.25M', target: '1.5M', progress: 83, trend: 12.3, status: 'on_track', statusLabel: '{{ __("On Track") }}', icon: 'fas fa-eye', iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
            { name: '{{ __("Click-Through Rate") }}', current: '3.45%', target: '4.0%', progress: 86, trend: -2.1, status: 'at_risk', statusLabel: '{{ __("At Risk") }}', icon: 'fas fa-mouse-pointer', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
            { name: '{{ __("Conversion Rate") }}', current: '2.8%', target: '3.5%', progress: 80, trend: 5.2, status: 'on_track', statusLabel: '{{ __("On Track") }}', icon: 'fas fa-shopping-cart', iconBg: 'bg-amber-100', iconColor: 'text-amber-600' },
            { name: '{{ __("Cost Per Click") }}', current: '$0.45', target: '$0.50', progress: 110, trend: -8.3, status: 'on_track', statusLabel: '{{ __("On Track") }}', icon: 'fas fa-dollar-sign', iconBg: 'bg-emerald-100', iconColor: 'text-emerald-600' },
            { name: '{{ __("ROAS") }}', current: '4.2x', target: '5.0x', progress: 84, trend: 15.7, status: 'on_track', statusLabel: '{{ __("On Track") }}', icon: 'fas fa-chart-line', iconBg: 'bg-indigo-100', iconColor: 'text-indigo-600' }
        ],

        async init() {
            await this.fetchData();
            this.$nextTick(() => this.initCharts());
        },

        async fetchData() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/analytics/kpis?entity_type=${this.entityType}&entity_id=${this.entityId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.kpis) this.kpis = data.kpis;
                    if (data.kpiList) this.kpiList = data.kpiList;
                    if (data.healthScore) this.healthScore = data.healthScore;
                }
            } catch (error) {
                console.warn('[KPI Dashboard] Using sample data:', error);
            } finally {
                this.loading = false;
            }
        },

        async refreshData() {
            await this.fetchData();
            this.updateCharts();
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(2) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        initCharts() {
            const ctx = document.getElementById('performanceChart');
            if (!ctx || typeof Chart === 'undefined') return;

            if (this.performanceChart) this.performanceChart.destroy();

            const labels = this.getChartLabels();

            this.performanceChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ __("Impressions") }}',
                            data: this.generateTrendData(labels.length, 100000, 150000),
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: '{{ __("Clicks") }}',
                            data: this.generateTrendData(labels.length, 3000, 5000),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        },

        getChartLabels() {
            const days = this.chartPeriod === '7d' ? 7 : this.chartPeriod === '30d' ? 30 : 90;
            const labels = [];
            for (let i = days - 1; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            }
            return labels;
        },

        generateTrendData(count, min, max) {
            const data = [];
            let current = min + Math.random() * (max - min);
            for (let i = 0; i < count; i++) {
                current += (Math.random() - 0.45) * (max - min) * 0.1;
                current = Math.max(min * 0.8, Math.min(max * 1.2, current));
                data.push(Math.round(current));
            }
            return data;
        },

        updateCharts() {
            if (this.performanceChart) {
                const labels = this.getChartLabels();
                this.performanceChart.data.labels = labels;
                this.performanceChart.data.datasets[0].data = this.generateTrendData(labels.length, 100000, 150000);
                this.performanceChart.data.datasets[1].data = this.generateTrendData(labels.length, 3000, 5000);
                this.performanceChart.update();
            }
        }
    };
}
</script>
@endpush
@endsection

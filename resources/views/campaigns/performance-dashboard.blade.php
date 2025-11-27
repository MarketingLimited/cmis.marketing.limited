@extends('layouts.admin')

@section('title', __('campaigns.performance_dashboard'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@push('styles')
<style>
    .metric-card {
        @apply bg-white rounded-lg shadow-md p-6 border border-gray-200;
    }

    .metric-card-header {
        @apply flex items-center justify-between mb-4;
    }

    .metric-value {
        @apply text-3xl font-bold text-gray-900;
    }

    .metric-label {
        @apply text-sm font-medium text-gray-600 uppercase tracking-wide;
    }

    .metric-change {
        @apply text-sm font-medium;
    }

    .metric-change.positive {
        @apply text-green-600;
    }

    .metric-change.negative {
        @apply text-red-600;
    }

    .chart-container {
        @apply bg-white rounded-lg shadow-md p-6 border border-gray-200;
        min-height: 400px;
    }

    .campaign-card {
        @apply bg-white rounded-lg shadow-sm p-4 border border-gray-200 hover:shadow-md transition-shadow cursor-pointer;
    }

    .campaign-card.selected {
        @apply border-blue-500 bg-blue-50;
    }

    .loading-overlay {
        @apply absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10;
    }
</style>
@endpush

@section('content')
<div x-data="campaignDashboard()" x-init="init()" class="campaign-performance-dashboard" dir="{{ $dir }}">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-3xl font-bold text-gray-900">{{ __('campaigns.performance_dashboard') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('campaigns.analyze_compare_performance') }}</p>
            </div>

            <!-- Date Range Picker -->
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-4' : 'space-x-4' }}">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.date_range') }}</label>
                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-2' : 'space-x-2' }}">
                        <input
                            type="date"
                            x-model="dateRange.start"
                            @change="loadDashboardData()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                            dir="ltr"
                        >
                        <span class="text-gray-500">{{ __('campaigns.to') }}</span>
                        <input
                            type="date"
                            x-model="dateRange.end"
                            @change="loadDashboardData()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                            dir="ltr"
                        >
                    </div>
                </div>

                <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-2' : 'space-x-2' }} pt-6">
                    <button
                        @click="setDateRange('7d')"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': datePreset === '7d'}"
                    >
                        {{ __('campaigns.last_7_days') }}
                    </button>
                    <button
                        @click="setDateRange('30d')"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': datePreset === '30d'}"
                    >
                        {{ __('campaigns.last_30_days') }}
                    </button>
                    <button
                        @click="setDateRange('90d')"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': datePreset === '90d'}"
                    >
                        {{ __('campaigns.last_90_days') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" class="loading-overlay">
        <div class="flex flex-col items-center">
            <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-4 text-sm font-medium text-gray-600">{{ __('campaigns.loading_dashboard') }}</p>
        </div>
    </div>

    <!-- Campaign Selector -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.select_campaign') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="campaign in campaigns" :key="campaign.campaign_id">
                <div
                    class="campaign-card"
                    :class="{'selected': selectedCampaignId === campaign.campaign_id}"
                    @click="selectCampaign(campaign.campaign_id)"
                >
                    <div class="flex items-center justify-between mb-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}" x-text="campaign.name"></h3>
                        <span
                            class="px-2 py-1 text-xs font-medium rounded-full"
                            :class="{
                                'bg-green-100 text-green-800': campaign.status === 'active',
                                'bg-gray-100 text-gray-800': campaign.status === 'paused',
                                'bg-blue-100 text-blue-800': campaign.status === 'scheduled'
                            }"
                            x-text="campaign.status"
                        ></span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2 {{ $isRtl ? 'text-right' : '' }}" x-text="campaign.description"></p>
                    <div class="flex items-center justify-between text-xs text-gray-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <span x-text="'{{ __('campaigns.budget') }}: ' + ('{{ $isRtl ? 'ر.س' : '$' }}') + (campaign.budget || 0).toLocaleString()"></span>
                        <span x-text="campaign.platform"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Key Metrics Cards -->
    <div x-show="selectedCampaignId && currentMetrics" class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.key_performance_indicators') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Impressions -->
            <div class="metric-card">
                <div class="metric-card-header {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="metric-label">{{ __('campaigns.metrics.impressions') }}</p>
                        <p class="metric-value" x-text="formatNumber(currentMetrics?.metrics?.impressions || 0)"></p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-eye text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Clicks -->
            <div class="metric-card">
                <div class="metric-card-header {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="metric-label">{{ __('campaigns.metrics.clicks') }}</p>
                        <p class="metric-value" x-text="formatNumber(currentMetrics?.metrics?.clicks || 0)"></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-mouse-pointer text-2xl text-green-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.metrics.ctr') }}: <span class="font-semibold" x-text="(currentMetrics?.metrics?.ctr || 0).toFixed(2) + '%'"></span>
                </p>
            </div>

            <!-- Conversions -->
            <div class="metric-card">
                <div class="metric-card-header {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="metric-label">{{ __('campaigns.metrics.conversions') }}</p>
                        <p class="metric-value" x-text="formatNumber(currentMetrics?.metrics?.conversions || 0)"></p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-purple-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.metrics.cost_per_conversion') }}: <span class="font-semibold" x-text="'{{ $isRtl ? 'ر.س' : '$' }}' + (currentMetrics?.metrics?.cpa || 0).toFixed(2)"></span>
                </p>
            </div>

            <!-- ROI -->
            <div class="metric-card">
                <div class="metric-card-header {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <p class="metric-label">{{ __('campaigns.metrics.roas') }}</p>
                        <p class="metric-value" x-text="(currentMetrics?.metrics?.roi || 0).toFixed(1) + '%'"></p>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-chart-line text-2xl text-yellow-600"></i>
                    </div>
                </div>
                <p class="text-sm text-gray-600 mt-2 {{ $isRtl ? 'text-right' : '' }}">
                    {{ __('campaigns.spent') }}: <span class="font-semibold" x-text="'{{ $isRtl ? 'ر.س' : '$' }}' + formatNumber(currentMetrics?.metrics?.spend || 0)"></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Performance Trends Chart -->
    <div x-show="selectedCampaignId" class="mb-8">
        <div class="chart-container">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-xl font-semibold text-gray-900">{{ __('campaigns.performance_trends') }}</h2>
                <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-2' : 'space-x-2' }}">
                    <button
                        @click="setTrendInterval('day')"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': trendInterval === 'day'}"
                    >
                        {{ __('campaigns.daily') }}
                    </button>
                    <button
                        @click="setTrendInterval('week')"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': trendInterval === 'week'}"
                    >
                        {{ __('campaigns.weekly') }}
                    </button>
                    <button
                        @click="setTrendInterval('month')"
                        class="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        :class="{'bg-blue-50 border-blue-500': trendInterval === 'month'}"
                    >
                        {{ __('campaigns.monthly') }}
                    </button>
                </div>
            </div>
            <canvas id="trendsChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Top Performing Campaigns -->
    <div class="mb-8">
        <div class="chart-container">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-xl font-semibold text-gray-900">{{ __('campaigns.top_performing_campaigns') }}</h2>
                <select
                    x-model="topCampaignsMetric"
                    @change="loadTopCampaigns()"
                    class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                >
                    <option value="conversions">{{ __('campaigns.by_conversions') }}</option>
                    <option value="clicks">{{ __('campaigns.by_clicks') }}</option>
                    <option value="impressions">{{ __('campaigns.by_impressions') }}</option>
                    <option value="roi">{{ __('campaigns.by_roi') }}</option>
                    <option value="spend">{{ __('campaigns.by_spend') }}</option>
                </select>
            </div>
            <canvas id="topCampaignsChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Campaign Comparison -->
    <div class="mb-8">
        <div class="chart-container">
            <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-xl font-semibold text-gray-900">{{ __('campaigns.campaign_comparison') }}</h2>
                <button
                    @click="showComparisonModal = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}"
                >
                    <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('campaigns.select_campaigns_to_compare') }}
                </button>
            </div>
            <div x-show="comparedCampaigns.length > 0">
                <canvas id="comparisonChart" width="400" height="200"></canvas>
            </div>
            <div x-show="comparedCampaigns.length === 0" class="text-center py-12">
                <i class="fas fa-chart-bar text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">{{ __('campaigns.select_campaigns_compare_performance') }}</p>
            </div>
        </div>
    </div>

    <!-- Comparison Modal -->
    <div
        x-show="showComparisonModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="showComparisonModal = false"
    >
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full p-6">
                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('campaigns.select_campaigns_to_compare') }}</h3>
                    <button @click="showComparisonModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    <template x-for="campaign in campaigns" :key="campaign.campaign_id">
                        <label class="flex items-center p-3 hover:bg-gray-50 rounded cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input
                                type="checkbox"
                                :value="campaign.campaign_id"
                                :checked="selectedComparisonCampaigns.includes(campaign.campaign_id)"
                                @change="toggleComparisonCampaign(campaign.campaign_id)"
                                :disabled="selectedComparisonCampaigns.length >= 10 && !selectedComparisonCampaigns.includes(campaign.campaign_id)"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded {{ $isRtl ? 'ml-3' : 'mr-3' }}"
                            >
                            <span class="flex-1 {{ $isRtl ? 'text-right mr-3' : '' }}">
                                <span class="font-medium text-gray-900" x-text="campaign.name"></span>
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-500" x-text="'(' + campaign.platform + ')'"></span>
                            </span>
                        </label>
                    </template>
                </div>

                <div class="mt-4 flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <p class="text-sm text-gray-600 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.selected') }}: <span class="font-semibold" x-text="selectedComparisonCampaigns.length"></span> / 10
                    </p>
                    <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
                        <button
                            @click="showComparisonModal = false"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                        >
                            {{ __('campaigns.cancel') }}
                        </button>
                        <button
                            @click="compareCampaigns()"
                            :disabled="selectedComparisonCampaigns.length < 2"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ __('campaigns.compare_campaigns') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function campaignDashboard() {
    return {
        loading: false,
        campaigns: [],
        selectedCampaignId: null,
        currentMetrics: null,
        dateRange: {
            start: null,
            end: null
        },
        datePreset: '30d',
        trendInterval: 'day',
        topCampaignsMetric: 'conversions',
        showComparisonModal: false,
        selectedComparisonCampaigns: [],
        comparedCampaigns: [],

        // Chart instances
        trendsChart: null,
        topCampaignsChart: null,
        comparisonChart: null,

        init() {
            // Set default date range (last 30 days)
            this.setDateRange('30d');

            // Load campaigns
            this.loadCampaigns();
        },

        setDateRange(preset) {
            this.datePreset = preset;
            const end = new Date();
            const start = new Date();

            switch(preset) {
                case '7d':
                    start.setDate(end.getDate() - 7);
                    break;
                case '30d':
                    start.setDate(end.getDate() - 30);
                    break;
                case '90d':
                    start.setDate(end.getDate() - 90);
                    break;
            }

            this.dateRange.start = start.toISOString().split('T')[0];
            this.dateRange.end = end.toISOString().split('T')[0];

            if (this.selectedCampaignId) {
                this.loadDashboardData();
            }
        },

        async loadCampaigns() {
            try {
                this.loading = true;
                const response = await fetch('/api/campaigns', {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('{{ __('campaigns.failed_load_campaigns') }}');

                const data = await response.json();
                this.campaigns = data.data || data;

                // Auto-select first campaign
                if (this.campaigns.length > 0 && !this.selectedCampaignId) {
                    this.selectCampaign(this.campaigns[0].campaign_id);
                }
            } catch (error) {
                console.error('Failed to load campaigns:', error);
                this.showError('{{ __('campaigns.failed_load_campaigns') }}');
            } finally {
                this.loading = false;
            }
        },

        selectCampaign(campaignId) {
            this.selectedCampaignId = campaignId;
            this.loadDashboardData();
        },

        async loadDashboardData() {
            if (!this.selectedCampaignId) return;

            await Promise.all([
                this.loadPerformanceMetrics(),
                this.loadPerformanceTrends(),
                this.loadTopCampaigns()
            ]);
        },

        async loadPerformanceMetrics() {
            try {
                const params = new URLSearchParams({
                    start_date: this.dateRange.start,
                    end_date: this.dateRange.end
                });

                const response = await fetch(`/api/campaigns/${this.selectedCampaignId}/performance-metrics?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('{{ __('campaigns.failed_load_metrics') }}');

                const data = await response.json();
                this.currentMetrics = data.data;
            } catch (error) {
                console.error('Failed to load performance metrics:', error);
            }
        },

        async loadPerformanceTrends() {
            try {
                const params = new URLSearchParams({
                    interval: this.trendInterval,
                    periods: this.datePreset === '7d' ? 7 : this.datePreset === '30d' ? 30 : 90
                });

                const response = await fetch(`/api/campaigns/${this.selectedCampaignId}/performance-trends?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('{{ __('campaigns.failed_load_trends') }}');

                const data = await response.json();
                this.renderTrendsChart(data.data);
            } catch (error) {
                console.error('Failed to load performance trends:', error);
            }
        },

        async loadTopCampaigns() {
            try {
                const params = new URLSearchParams({
                    metric: this.topCampaignsMetric,
                    limit: 10,
                    start_date: this.dateRange.start,
                    end_date: this.dateRange.end
                });

                const response = await fetch(`/api/campaigns/top-performing?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('{{ __('campaigns.failed_load_top_campaigns') }}');

                const data = await response.json();
                this.renderTopCampaignsChart(data.data);
            } catch (error) {
                console.error('Failed to load top campaigns:', error);
            }
        },

        setTrendInterval(interval) {
            this.trendInterval = interval;
            this.loadPerformanceTrends();
        },

        toggleComparisonCampaign(campaignId) {
            const index = this.selectedComparisonCampaigns.indexOf(campaignId);
            if (index > -1) {
                this.selectedComparisonCampaigns.splice(index, 1);
            } else if (this.selectedComparisonCampaigns.length < 10) {
                this.selectedComparisonCampaigns.push(campaignId);
            }
        },

        async compareCampaigns() {
            if (this.selectedComparisonCampaigns.length < 2) return;

            try {
                this.loading = true;
                const response = await fetch('/api/campaigns/compare', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        campaign_ids: this.selectedComparisonCampaigns,
                        start_date: this.dateRange.start,
                        end_date: this.dateRange.end
                    })
                });

                if (!response.ok) throw new Error('{{ __('campaigns.failed_compare_campaigns') }}');

                const data = await response.json();
                this.comparedCampaigns = data.data.campaigns;
                this.renderComparisonChart(data.data);
                this.showComparisonModal = false;
            } catch (error) {
                console.error('Failed to compare campaigns:', error);
                this.showError('{{ __('campaigns.failed_compare_campaigns') }}');
            } finally {
                this.loading = false;
            }
        },

        renderTrendsChart(data) {
            const ctx = document.getElementById('trendsChart');
            if (!ctx) return;

            if (this.trendsChart) {
                this.trendsChart.destroy();
            }

            this.trendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.trends?.map(t => t.period) || [],
                    datasets: [
                        {
                            label: '{{ __('campaigns.metrics.impressions') }}',
                            data: data.trends?.map(t => t.metrics.impressions) || [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            yAxisID: 'y'
                        },
                        {
                            label: '{{ __('campaigns.metrics.clicks') }}',
                            data: data.trends?.map(t => t.metrics.clicks) || [],
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            yAxisID: 'y'
                        },
                        {
                            label: '{{ __('campaigns.metrics.conversions') }}',
                            data: data.trends?.map(t => t.metrics.conversions) || [],
                            borderColor: 'rgb(139, 92, 246)',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
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
                            position: '{{ $isRtl ? 'right' : 'left' }}',
                            title: {
                                display: true,
                                text: '{{ __('campaigns.impressions_clicks') }}'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: '{{ $isRtl ? 'left' : 'right' }}',
                            title: {
                                display: true,
                                text: '{{ __('campaigns.metrics.conversions') }}'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        },

        renderTopCampaignsChart(data) {
            const ctx = document.getElementById('topCampaignsChart');
            if (!ctx) return;

            if (this.topCampaignsChart) {
                this.topCampaignsChart.destroy();
            }

            const campaigns = data.campaigns || [];

            this.topCampaignsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: campaigns.map(c => c.campaign_name),
                    datasets: [{
                        label: this.topCampaignsMetric.charAt(0).toUpperCase() + this.topCampaignsMetric.slice(1),
                        data: campaigns.map(c => c.value),
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        renderComparisonChart(data) {
            const ctx = document.getElementById('comparisonChart');
            if (!ctx) return;

            if (this.comparisonChart) {
                this.comparisonChart.destroy();
            }

            const campaigns = data.campaigns || [];

            this.comparisonChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: campaigns.map(c => c.campaign_name),
                    datasets: [
                        {
                            label: '{{ __('campaigns.metrics.impressions') }}',
                            data: campaigns.map(c => c.metrics.impressions),
                            backgroundColor: 'rgba(59, 130, 246, 0.8)'
                        },
                        {
                            label: '{{ __('campaigns.metrics.clicks') }}',
                            data: campaigns.map(c => c.metrics.clicks),
                            backgroundColor: 'rgba(16, 185, 129, 0.8)'
                        },
                        {
                            label: '{{ __('campaigns.metrics.conversions') }}',
                            data: campaigns.map(c => c.metrics.conversions),
                            backgroundColor: 'rgba(139, 92, 246, 0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: '{{ __('campaigns.campaign_performance_comparison') }}'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        formatNumber(num) {
            return new Intl.NumberFormat('{{ app()->getLocale() }}').format(num);
        },

        getAuthToken() {
            // Get token from meta tag or localStorage
            const meta = document.querySelector('meta[name="api-token"]');
            return meta ? meta.content : localStorage.getItem('auth_token') || '';
        },

        showError(message) {
            // Use your preferred notification system
            alert(message);
        }
    };
}
</script>
@endpush

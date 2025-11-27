@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('Campaign Analytics') . ' - ' . $campaign->name)

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.analytics.enterprise', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Analytics') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.analytics.campaigns', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $campaign->name }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $campaign->name }}</h1>
                <p class="text-gray-600 mt-1">{{ __('ROI analysis, attribution modeling, and performance projections') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('orgs.campaigns.edit', ['org' => $currentOrg, 'campaign' => $campaignId]) }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span>{{ __('Edit Campaign') }}</span>
                </a>
                <a href="{{ route('orgs.analytics.campaigns', $currentOrg) }}"
                   class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>{{ __('Back to Campaigns') }}</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Campaign Analytics Component --}}
    <div x-data="campaignAnalytics()"
         data-org-id="{{ $orgId }}"
         data-campaign-id="{{ $campaignId }}"
         x-init="init()"
         class="space-y-6">

        {{-- Header with Date Range and Model Selector --}}
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ __('Campaign Performance Analytics') }}</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ __('Status') }}: <span class="font-semibold">{{ ucfirst($campaign->status) }}</span>
                        <span class="mx-2">|</span>
                        {{ __('Duration') }}: {{ $campaign->start_date }} {{ __('to') }} {{ $campaign->end_date ?? __('Ongoing') }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    {{-- Date Range --}}
                    <div class="flex items-center gap-2">
                        <input type="date"
                               x-model="dateRange.start"
                               @change="updateDateRange()"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <span class="text-gray-600">{{ __('to') }}</span>
                        <input type="date"
                               x-model="dateRange.end"
                               @change="updateDateRange()"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </div>

                    {{-- Attribution Model Selector --}}
                    <select x-model="attributionModel"
                            @change="changeAttributionModel(attributionModel)"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="last-click">{{ __('Last-Click Attribution') }}</option>
                        <option value="first-click">{{ __('First-Click Attribution') }}</option>
                        <option value="linear">{{ __('Linear Attribution') }}</option>
                        <option value="time-decay">{{ __('Time-Decay Attribution') }}</option>
                        <option value="position-based">{{ __('Position-Based Attribution') }}</option>
                        <option value="data-driven">{{ __('Data-Driven Attribution') }}</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="bg-white rounded-xl shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'overview'"
                            :class="activeTab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('Overview') }}
                    </button>
                    <button @click="activeTab = 'roi'"
                            :class="activeTab === 'roi' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('ROI Analysis') }}
                    </button>
                    <button @click="activeTab = 'attribution'"
                            :class="activeTab === 'attribution' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('Attribution') }}
                    </button>
                    <button @click="activeTab = 'ltv'"
                            :class="activeTab === 'ltv' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('Lifetime Value') }}
                    </button>
                    <button @click="activeTab = 'projection'"
                            :class="activeTab === 'projection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('Projections') }}
                    </button>
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="p-6">
                {{-- Overview Tab --}}
                <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-blue-600">{{ __('Total Spend') }}</p>
                            <p class="text-3xl font-bold text-blue-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.total_spend) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-green-600">{{ __('Total Revenue') }}</p>
                            <p class="text-3xl font-bold text-green-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.total_revenue) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-purple-600">{{ __('Profit') }}</p>
                            <p class="text-3xl font-bold text-purple-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.profit) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-orange-600">{{ __('ROI') }}</p>
                            <p class="text-3xl font-bold text-orange-900 mt-2" x-text="roiData ? formatPercentage(roiData.financial_metrics?.roi_percentage) : '-'"></p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('Profitability Status') }}</h4>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-2xl font-bold"
                                   :class="roiData ? getProfitabilityColor(roiData.profitability?.status) : 'text-gray-400'"
                                   x-text="roiData ? roiData.profitability?.status.replace('_', ' ').toUpperCase() : 'N/A'"></p>
                                <p class="text-sm text-gray-600 mt-1" x-text="roiData?.profitability?.message || '{{ __('Loading...') }}'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">{{ __('Break-even Point') }}</p>
                                <p class="text-xl font-bold text-gray-900" x-text="roiData ? formatCurrency(roiData.profitability?.break_even_point) : '-'"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ROI Analysis Tab --}}
                <div x-show="activeTab === 'roi'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('ROI Breakdown') }}</h4>
                            <div style="height: 300px;">
                                <canvas id="roiChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('Financial Metrics') }}</h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">{{ __('Gross Profit Margin') }}</span>
                                    <span class="font-semibold" x-text="roiData ? formatPercentage(roiData.profitability?.gross_margin) : '-'"></span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">{{ __('Net Profit Margin') }}</span>
                                    <span class="font-semibold" x-text="roiData ? formatPercentage(roiData.profitability?.net_margin) : '-'"></span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">{{ __('ROAS (Return on Ad Spend)') }}</span>
                                    <span class="font-semibold" x-text="roiData ? roiData.financial_metrics?.roas?.toFixed(2) + 'x' : '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Attribution Tab --}}
                <div x-show="activeTab === 'attribution'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('Channel Attribution') }}</h4>
                            <div style="height: 300px;">
                                <canvas id="attributionChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('Channel Insights') }}</h4>
                            <div class="space-y-3">
                                <template x-for="insight in (attributionData?.insights || [])" :key="insight.channel">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-semibold text-gray-900" x-text="insight.channel"></span>
                                            <span class="text-sm text-blue-600 font-semibold" x-text="insight.contribution_percentage?.toFixed(2) + '%'"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-600">{{ __('Touchpoints') }}:</span>
                                                <span class="font-medium ml-1" x-text="insight.touchpoints"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">{{ __('Conversions') }}:</span>
                                                <span class="font-medium ml-1" x-text="insight.attributed_conversions?.toFixed(0)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- LTV Tab --}}
                <div x-show="activeTab === 'ltv'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-blue-600">{{ __('Average LTV') }}</p>
                            <p class="text-3xl font-bold text-blue-900 mt-2" x-text="ltvData ? formatCurrency(ltvData.ltv?.average_ltv) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-teal-600">{{ __('Total Customer Value') }}</p>
                            <p class="text-3xl font-bold text-teal-900 mt-2" x-text="ltvData ? formatCurrency(ltvData.ltv?.total_customer_value) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-pink-600">{{ __('LTV/CAC Ratio') }}</p>
                            <p class="text-3xl font-bold text-pink-900 mt-2" x-text="ltvData ? ltvData.ltv?.ltv_cac_ratio?.toFixed(2) + 'x' : '-'"></p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">{{ __('LTV Analysis') }}</h4>
                        <div class="prose max-w-none">
                            <p class="text-gray-600">
                                {{ __('A healthy LTV/CAC ratio should be 3:1 or higher, indicating that each customer generates three times more value than the cost to acquire them.') }}
                            </p>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-600">{{ __('Customer Acquisition Cost') }}:</span>
                                    <p class="text-xl font-bold text-gray-900" x-text="ltvData ? formatCurrency(ltvData.ltv?.customer_acquisition_cost) : '-'"></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">{{ __('Payback Period') }}:</span>
                                    <p class="text-xl font-bold text-gray-900" x-text="ltvData ? ltvData.ltv?.payback_period_days + ' {{ __('days') }}' : '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Projections Tab --}}
                <div x-show="activeTab === 'projection'" x-cloak class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-bold text-gray-900">{{ __('30-Day Projection') }}</h4>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-600">{{ __('Confidence Level') }}:</span>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold"
                                      :class="projection?.confidence_level.level === 'high' ? 'bg-green-100 text-green-700' : projection?.confidence_level.level === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                                      x-text="projection ? projection.confidence_level.level + ' (' + projection.confidence_level.percentage + '%)' : '-'"></span>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="projectionChart"></canvas>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-blue-600">{{ __('Projected Spend') }}</p>
                            <p class="text-2xl font-bold text-blue-900 mt-2" x-text="projection ? formatCurrency(projection.projected_metrics?.projected_spend) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6">
                            <p class="text-sm font-medium text-green-600">{{ __('Projected Revenue') }}</p>
                            <p class="text-2xl font-bold text-green-900 mt-2" x-text="projection ? formatCurrency(projection.projected_metrics?.projected_revenue) : '-'"></p>
                        </div>
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
function campaignAnalytics() {
    return {
        orgId: null,
        campaignId: null,
        activeTab: 'overview',
        dateRange: { start: '', end: '' },
        attributionModel: 'last-click',
        loading: false,
        roiData: null,
        attributionData: null,
        ltvData: null,
        projection: null,
        roiChart: null,
        attributionChart: null,
        projectionChart: null,

        init() {
            this.orgId = this.$el.dataset.orgId;
            this.campaignId = this.$el.dataset.campaignId;

            // Set default date range (last 30 days)
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);
            this.dateRange.start = start.toISOString().split('T')[0];
            this.dateRange.end = end.toISOString().split('T')[0];

            this.loadAllData();
        },

        async loadAllData() {
            this.loading = true;
            try {
                // Load sample data for demo
                this.loadSampleData();
            } catch (error) {
                console.error('Failed to load analytics data:', error);
            } finally {
                this.loading = false;
            }
        },

        loadSampleData() {
            this.roiData = {
                financial_metrics: {
                    total_spend: 15000,
                    total_revenue: 45000,
                    profit: 30000,
                    roi_percentage: 200,
                    roas: 3.0
                },
                profitability: {
                    status: 'profitable',
                    message: 'Campaign is generating strong returns',
                    break_even_point: 15000,
                    gross_margin: 66.7,
                    net_margin: 60.0
                }
            };

            this.attributionData = {
                insights: [
                    { channel: 'Paid Search', contribution_percentage: 35, touchpoints: 1250, attributed_conversions: 175 },
                    { channel: 'Social Media', contribution_percentage: 28, touchpoints: 980, attributed_conversions: 140 },
                    { channel: 'Email', contribution_percentage: 22, touchpoints: 750, attributed_conversions: 110 },
                    { channel: 'Direct', contribution_percentage: 15, touchpoints: 520, attributed_conversions: 75 }
                ]
            };

            this.ltvData = {
                ltv: {
                    average_ltv: 450,
                    total_customer_value: 225000,
                    ltv_cac_ratio: 4.5,
                    customer_acquisition_cost: 100,
                    payback_period_days: 45
                }
            };

            this.projection = {
                confidence_level: { level: 'high', percentage: 85 },
                projected_metrics: {
                    projected_spend: 18000,
                    projected_revenue: 54000
                }
            };

            this.$nextTick(() => {
                this.initCharts();
            });
        },

        initCharts() {
            this.initROIChart();
            this.initAttributionChart();
            this.initProjectionChart();
        },

        initROIChart() {
            const ctx = document.getElementById('roiChart');
            if (!ctx) return;

            if (this.roiChart) this.roiChart.destroy();

            this.roiChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['{{ __('Revenue') }}', '{{ __('Spend') }}', '{{ __('Profit') }}'],
                    datasets: [{
                        data: [
                            this.roiData?.financial_metrics?.total_revenue || 0,
                            this.roiData?.financial_metrics?.total_spend || 0,
                            this.roiData?.financial_metrics?.profit || 0
                        ],
                        backgroundColor: ['#10B981', '#EF4444', '#6366F1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        },

        initAttributionChart() {
            const ctx = document.getElementById('attributionChart');
            if (!ctx) return;

            if (this.attributionChart) this.attributionChart.destroy();

            const insights = this.attributionData?.insights || [];
            this.attributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: insights.map(i => i.channel),
                    datasets: [{
                        label: '{{ __('Contribution %') }}',
                        data: insights.map(i => i.contribution_percentage),
                        backgroundColor: ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B'],
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        },

        initProjectionChart() {
            const ctx = document.getElementById('projectionChart');
            if (!ctx) return;

            if (this.projectionChart) this.projectionChart.destroy();

            const days = Array.from({ length: 30 }, (_, i) => `Day ${i + 1}`);
            const spendProjection = days.map((_, i) => (this.projection?.projected_metrics?.projected_spend || 0) * (i + 1) / 30);
            const revenueProjection = days.map((_, i) => (this.projection?.projected_metrics?.projected_revenue || 0) * (i + 1) / 30);

            this.projectionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: days,
                    datasets: [
                        {
                            label: '{{ __('Projected Spend') }}',
                            data: spendProjection,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: '{{ __('Projected Revenue') }}',
                            data: revenueProjection,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        updateDateRange() {
            this.loadAllData();
        },

        changeAttributionModel(model) {
            this.attributionModel = model;
            this.loadAllData();
        },

        formatCurrency(value) {
            if (value === null || value === undefined) return '-';
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
        },

        formatPercentage(value) {
            if (value === null || value === undefined) return '-';
            return value.toFixed(1) + '%';
        },

        getProfitabilityColor(status) {
            const colors = {
                'profitable': 'text-green-600',
                'break_even': 'text-yellow-600',
                'loss': 'text-red-600'
            };
            return colors[status] || 'text-gray-600';
        }
    };
}
</script>
@endpush

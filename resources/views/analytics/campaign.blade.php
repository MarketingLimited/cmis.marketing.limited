@extends('layouts.analytics')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'Campaign Analytics - ' . $campaign->name)

@section('page-title', $campaign->name)
@section('page-subtitle', 'ROI analysis, attribution modeling, and performance projections')

@section('content')
<div class="space-y-6">
    <!-- Campaign Analytics Component -->
    <div x-data="campaignAnalytics()"
         data-org-id="{{ $orgId }}"
         data-campaign-id="{{ $campaignId }}"
         x-init="init()"
         class="space-y-6">

        <!-- Header with Date Range and Model Selector -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Campaign Performance Analytics</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        Status: <span class="font-semibold">{{ ucfirst($campaign->status) }}</span>
                        <span class="mx-2">|</span>
                        Duration: {{ $campaign->start_date }} to {{ $campaign->end_date ?? 'Ongoing' }}
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Date Range -->
                    <div class="flex items-center gap-2">
                        <input type="date"
                               x-model="dateRange.start"
                               @change="updateDateRange()"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <span class="text-gray-600">to</span>
                        <input type="date"
                               x-model="dateRange.end"
                               @change="updateDateRange()"
                               class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    </div>

                    <!-- Attribution Model Selector -->
                    <select x-model="attributionModel"
                            @change="changeAttributionModel(attributionModel)"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="last-click">Last-Click Attribution</option>
                        <option value="first-click">First-Click Attribution</option>
                        <option value="linear">Linear Attribution</option>
                        <option value="time-decay">Time-Decay Attribution</option>
                        <option value="position-based">Position-Based Attribution</option>
                        <option value="data-driven">Data-Driven Attribution</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'overview'"
                            :class="activeTab === 'overview' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Overview
                    </button>
                    <button @click="activeTab = 'roi'"
                            :class="activeTab === 'roi' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        ROI Analysis
                    </button>
                    <button @click="activeTab = 'attribution'"
                            :class="activeTab === 'attribution' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Attribution
                    </button>
                    <button @click="activeTab = 'ltv'"
                            :class="activeTab === 'ltv' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Lifetime Value
                    </button>
                    <button @click="activeTab = 'projection'"
                            :class="activeTab === 'projection' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Projections
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Overview Tab -->
                <div x-show="activeTab === 'overview'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-blue-600">Total Spend</p>
                            <p class="text-3xl font-bold text-blue-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.total_spend) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-green-600">Total Revenue</p>
                            <p class="text-3xl font-bold text-green-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.total_revenue) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-purple-600">Profit</p>
                            <p class="text-3xl font-bold text-purple-900 mt-2" x-text="roiData ? formatCurrency(roiData.financial_metrics?.profit) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-orange-600">ROI</p>
                            <p class="text-3xl font-bold text-orange-900 mt-2" x-text="roiData ? formatPercentage(roiData.financial_metrics?.roi_percentage) : '-'"></p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Profitability Status</h4>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-2xl font-bold"
                                   :class="roiData ? getProfitabilityColor(roiData.profitability?.status) : 'text-gray-400'"
                                   x-text="roiData ? roiData.profitability?.status.replace('_', ' ').toUpperCase() : 'N/A'"></p>
                                <p class="text-sm text-gray-600 mt-1" x-text="roiData?.profitability?.message || 'Loading...'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Break-even Point</p>
                                <p class="text-xl font-bold text-gray-900" x-text="roiData ? formatCurrency(roiData.profitability?.break_even_point) : '-'"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ROI Analysis Tab -->
                <div x-show="activeTab === 'roi'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">ROI Breakdown</h4>
                            <div class="chart-container">
                                <canvas id="roiChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Financial Metrics</h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">Gross Profit Margin</span>
                                    <span class="font-semibold" x-text="roiData ? formatPercentage(roiData.profitability?.gross_margin) : '-'"></span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">Net Profit Margin</span>
                                    <span class="font-semibold" x-text="roiData ? formatPercentage(roiData.profitability?.net_margin) : '-'"></span>
                                </div>
                                <div class="flex justify-between items-center pb-3 border-b">
                                    <span class="text-gray-600">ROAS (Return on Ad Spend)</span>
                                    <span class="font-semibold" x-text="roiData ? roiData.financial_metrics?.roas?.toFixed(2) + 'x' : '-'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attribution Tab -->
                <div x-show="activeTab === 'attribution'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Channel Attribution</h4>
                            <div class="chart-container">
                                <canvas id="attributionChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 mb-4">Channel Insights</h4>
                            <div class="space-y-3">
                                <template x-for="insight in (attributionData?.insights || [])" :key="insight.channel">
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="font-semibold text-gray-900" x-text="insight.channel"></span>
                                            <span class="text-sm text-indigo-600 font-semibold" x-text="insight.contribution_percentage?.toFixed(2) + '%'"></span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-600">Touchpoints:</span>
                                                <span class="font-medium ml-1" x-text="insight.touchpoints"></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">Conversions:</span>
                                                <span class="font-medium ml-1" x-text="insight.attributed_conversions?.toFixed(0)"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LTV Tab -->
                <div x-show="activeTab === 'ltv'" x-cloak class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-indigo-600">Average LTV</p>
                            <p class="text-3xl font-bold text-indigo-900 mt-2" x-text="ltvData ? formatCurrency(ltvData.ltv?.average_ltv) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-teal-600">Total Customer Value</p>
                            <p class="text-3xl font-bold text-teal-900 mt-2" x-text="ltvData ? formatCurrency(ltvData.ltv?.total_customer_value) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-pink-600">LTV/CAC Ratio</p>
                            <p class="text-3xl font-bold text-pink-900 mt-2" x-text="ltvData ? ltvData.ltv?.ltv_cac_ratio?.toFixed(2) + 'x' : '-'"></p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">LTV Analysis</h4>
                        <div class="prose max-w-none">
                            <p class="text-gray-600">
                                A healthy LTV/CAC ratio should be 3:1 or higher, indicating that each customer generates
                                three times more value than the cost to acquire them.
                            </p>
                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-gray-600">Customer Acquisition Cost:</span>
                                    <p class="text-xl font-bold text-gray-900" x-text="ltvData ? formatCurrency(ltvData.ltv?.customer_acquisition_cost) : '-'"></p>
                                </div>
                                <div>
                                    <span class="text-sm text-gray-600">Payback Period:</span>
                                    <p class="text-xl font-bold text-gray-900" x-text="ltvData ? ltvData.ltv?.payback_period_days + ' days' : '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projections Tab -->
                <div x-show="activeTab === 'projection'" x-cloak class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-bold text-gray-900">30-Day Projection</h4>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-600">Confidence Level:</span>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold"
                                      :class="projection?.confidence_level.level === 'high' ? 'bg-green-100 text-green-700' : projection?.confidence_level.level === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                                      x-text="projection ? projection.confidence_level.level + ' (' + projection.confidence_level.percentage + '%)' : '-'"></span>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="projectionChart"></canvas>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-blue-600">Projected Spend</p>
                            <p class="text-2xl font-bold text-blue-900 mt-2" x-text="projection ? formatCurrency(projection.projected_metrics?.projected_spend) : '-'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6">
                            <p class="text-sm font-medium text-green-600">Projected Revenue</p>
                            <p class="text-2xl font-bold text-green-900 mt-2" x-text="projection ? formatCurrency(projection.projected_metrics?.projected_revenue) : '-'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    console.log('Campaign analytics initialized for campaign: {{ $campaignId }}');
</script>
@endpush
@endsection

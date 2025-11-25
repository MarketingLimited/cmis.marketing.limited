@extends('layouts.analytics')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'Real-Time Analytics')

@section('page-title', 'Real-Time Analytics Dashboard')
@section('page-subtitle', 'Live performance metrics with auto-refresh')

@section('content')
<div class="space-y-6">
    <!-- Real-Time Dashboard Component -->
    <div x-data="realtimeDashboard()"
         data-org-id="{{ $orgId }}"
         x-init="init()"
         class="space-y-6">

        <!-- Header with Time Window Selector -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Live Performance Metrics</h3>
                    <p class="text-sm text-gray-600 mt-1">Auto-refreshing every 30 seconds</p>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Time Window Selector -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Time Window:</label>
                        <select x-model="timeWindow"
                                @change="changeTimeWindow()"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="1m">1 Minute</option>
                            <option value="5m">5 Minutes</option>
                            <option value="15m">15 Minutes</option>
                            <option value="1h">1 Hour</option>
                        </select>
                    </div>

                    <!-- Auto-Refresh Toggle -->
                    <button @click="toggleAutoRefresh()"
                            :class="autoRefresh ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-lg font-medium transition flex items-center gap-2">
                        <i :class="autoRefresh ? 'fa-pause' : 'fa-play'" class="fas text-sm"></i>
                        <span x-text="autoRefresh ? 'Auto-Refresh ON' : 'Auto-Refresh OFF'"></span>
                    </button>

                    <!-- Manual Refresh -->
                    <button @click="loadDashboard()"
                            :disabled="loading"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition disabled:opacity-50 flex items-center gap-2">
                        <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading && !dashboardData" class="flex items-center justify-center py-12">
            <div class="spinner"></div>
        </div>

        <!-- Error State -->
        <div x-show="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                <p class="text-red-800 font-medium" x-text="error"></p>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div x-show="!loading && dashboardData" x-cloak class="space-y-6">
            <!-- Organization Totals -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Impressions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Impressions</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatNumber(totals.impressions || 0)"></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-4">
                            <i class="fas fa-eye text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Clicks -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Clicks</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatNumber(totals.clicks || 0)"></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-4">
                            <i class="fas fa-mouse-pointer text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Conversions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Conversions</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatNumber(totals.conversions || 0)"></p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-4">
                            <i class="fas fa-check-circle text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Spend -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Spend</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2" x-text="formatCurrency(totals.spend || 0)"></p>
                        </div>
                        <div class="bg-orange-100 rounded-full p-4">
                            <i class="fas fa-dollar-sign text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Derived Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- CTR -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600">Click-Through Rate (CTR)</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-2" x-text="derivedMetrics.ctr?.toFixed(2) + '%' || '0.00%'"></p>
                </div>

                <!-- CPC -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600">Cost Per Click (CPC)</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-2" x-text="formatCurrency(derivedMetrics.cpc || 0)"></p>
                </div>

                <!-- Conversion Rate -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-sm font-medium text-gray-600">Conversion Rate</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-2" x-text="derivedMetrics.conversion_rate?.toFixed(2) + '%' || '0.00%'"></p>
                </div>
            </div>

            <!-- Campaign Performance Chart -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h4 class="text-lg font-bold text-gray-900 mb-4">Campaign Performance</h4>
                <div class="chart-container">
                    <canvas id="campaignPerformanceChart"></canvas>
                </div>
            </div>

            <!-- Active Campaigns Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h4 class="text-lg font-bold text-gray-900">Active Campaigns</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impressions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spend</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="campaign in campaigns" :key="campaign.campaign_id">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900" x-text="campaign.campaign_name"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="formatNumber(campaign.impressions)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="formatNumber(campaign.clicks)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="campaign.ctr?.toFixed(2) + '%'"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="formatCurrency(campaign.spend)"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a :href="`{{ route('orgs.analytics.campaign', ['org' => $currentOrg, 'campaign' => '']) }}`.slice(0, -1) + campaign.campaign_id"
                                           class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div x-show="campaigns.length === 0" class="text-center py-12">
                    <i class="fas fa-chart-bar text-gray-400 text-5xl mb-4"></i>
                    <p class="text-gray-500">No active campaigns found</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    // Authentication token is set in layout
    console.log('Real-time dashboard initialized');
</script>
@endpush
@endsection

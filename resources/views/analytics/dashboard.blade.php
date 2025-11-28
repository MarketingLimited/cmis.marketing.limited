@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('analytics.analytics_dashboard'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="analyticsDashboard()">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ __('analytics.analytics_dashboard') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('analytics.comprehensive_analysis') }}</p>
        </div>
        <div class="flex gap-3">
            <!-- Date Range Selector -->
            <select x-model="dateRange" @change="loadData()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="7">{{ __('analytics.last_7_days') }}</option>
                <option value="30">{{ __('analytics.last_30_days') }}</option>
                <option value="90">{{ __('dashboard.last_90_days') }}</option>
                <option value="365">{{ __('dashboard.this_year') }}</option>
            </select>
            <a href="{{ route('orgs.analytics.export', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="ms-2 -me-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('analytics.export') }}
            </a>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- Main Content -->
    <div x-show="!loading" x-cloak>
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <!-- Total Campaigns -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-indigo-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                        </div>
                        <div class="me-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('dashboard.total_campaigns') }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" x-text="stats.totalCampaigns || 0"></div>
                                    <div class="me-2 flex items-baseline text-sm font-semibold" :class="stats.campaignsChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <span x-text="stats.campaignsChange || 0"></span>%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Campaigns -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-green-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="me-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('dashboard.active_campaigns') }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" x-text="stats.activeCampaigns || 0"></div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Reach -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-blue-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="me-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">{{ __('analytics.reach') }}</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" x-text="formatNumber(stats.totalReach || 0)"></div>
                                    <div class="me-2 flex items-baseline text-sm font-semibold" :class="stats.reachChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <span x-text="stats.reachChange || 0"></span>%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Engagement Rate -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-md bg-yellow-500 p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mr-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">معدل التفاعل</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" x-text="(stats.engagementRate || 0) + '%'"></div>
                                    <div class="mr-2 flex items-baseline text-sm font-semibold" :class="stats.engagementChange >= 0 ? 'text-green-600' : 'text-red-600'">
                                        <span x-text="stats.engagementChange || 0"></span>%
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
            <!-- Performance Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('analytics.campaign_performance') }}</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                    <p class="text-gray-500">{{ __('analytics.chart') }} - {{ __('common.integration_with_chartjs') }}</p>
                </div>
            </div>

            <!-- Channel Distribution -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('analytics.distribution') }}</h3>
                <div class="h-64 flex items-center justify-center bg-gray-50 rounded">
                    <p class="text-gray-500">{{ __('analytics.chart') }} - {{ __('common.integration_with_chartjs') }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Campaigns Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('dashboard.recent_campaigns') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('analytics.campaign') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('analytics.status') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('analytics.reach') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('analytics.engagement') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('common.date') }}
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">{{ __('common.actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="campaign in recentCampaigns" :key="campaign.campaign_id || campaign.id">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900" x-text="campaign.name"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800': campaign.status === 'active',
                                              'bg-yellow-100 text-yellow-800': campaign.status === 'paused',
                                              'bg-gray-100 text-gray-800': campaign.status === 'draft',
                                              'bg-blue-100 text-blue-800': campaign.status === 'completed'
                                          }"
                                          x-text="campaign.status_label">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatNumber(campaign.reach || 0)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="(campaign.engagement || 0) + '%'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="campaign.created_at"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-start text-sm font-medium">
                                    <a :href="'/campaigns/' + (campaign.campaign_id || campaign.id)" class="text-indigo-600 hover:text-indigo-900">{{ __('common.view') }}</a>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="recentCampaigns.length === 0">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                {{ __('dashboard.no_campaigns') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function analyticsDashboard() {
    return {
        loading: true,
        dateRange: '30',
        stats: {
            totalCampaigns: 0,
            activeCampaigns: 0,
            totalReach: 0,
            engagementRate: 0,
            campaignsChange: 0,
            reachChange: 0,
            engagementChange: 0
        },
        recentCampaigns: [],

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const response = await fetch(`/api/analytics/dashboard?days=${this.dateRange}`);
                const data = await response.json();

                this.stats = data.stats || this.stats;
                this.recentCampaigns = data.recent_campaigns || [];
            } catch (error) {
                console.error('Error loading analytics:', error);
            } finally {
                this.loading = false;
            }
        },

        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
    }
}
</script>
@endpush
@endsection

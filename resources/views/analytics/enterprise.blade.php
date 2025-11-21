@extends('layouts.analytics')

@section('title', 'Enterprise Analytics Hub')

@section('page-title', 'Enterprise Analytics Hub')
@section('page-subtitle', 'Unified dashboard with real-time metrics, KPIs, and alerts')

@section('content')
<div class="space-y-6">
    <!-- Quick Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Campaigns</p>
                    <p class="text-3xl font-bold mt-2">{{ $activeCampaigns->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-bullhorn text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Real-Time Performance</p>
                    <p class="text-3xl font-bold mt-2"><i class="fas fa-bolt text-xl"></i></p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">KPI Monitoring</p>
                    <p class="text-3xl font-bold mt-2"><i class="fas fa-tachometer-alt text-xl"></i></p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-target text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">Active Alerts</p>
                    <p class="text-3xl font-bold mt-2" x-data="notificationCenter()" data-org-id="{{ $orgId }}" x-text="unreadCount" x-init="init()">0</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-bell text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div x-data="{ activeTab: 'realtime' }" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'realtime'"
                            :class="activeTab === 'realtime' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        <i class="fas fa-bolt mr-2"></i>Real-Time Dashboard
                    </button>
                    <button @click="activeTab = 'kpis'"
                            :class="activeTab === 'kpis' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>KPI Dashboard
                    </button>
                    <button @click="activeTab = 'campaigns'"
                            :class="activeTab === 'campaigns' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        <i class="fas fa-folder mr-2"></i>Campaign Overview
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Real-Time Dashboard Tab -->
                <div x-show="activeTab === 'realtime'" x-cloak>
                    <div x-data="realtimeDashboard()"
                         data-org-id="{{ $orgId }}"
                         x-init="init()"
                         class="space-y-6">
                        <!-- Mini version for enterprise hub -->
                        <div x-show="!loading && dashboardData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600">Impressions</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(totals.impressions || 0)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600">Clicks</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(totals.clicks || 0)"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600">CTR</p>
                                <p class="text-2xl font-bold text-indigo-600" x-text="derivedMetrics.ctr?.toFixed(2) + '%' || '0.00%'"></p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600">Spend</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(totals.spend || 0)"></p>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="{{ route('analytics.realtime') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                                <span>View Full Real-Time Dashboard</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- KPIs Dashboard Tab -->
                <div x-show="activeTab === 'kpis'" x-cloak>
                    <div x-data="kpiDashboard()"
                         data-org-id="{{ $orgId }}"
                         data-entity-type="org"
                         data-entity-id="{{ $orgId }}"
                         x-init="init()">
                        <!-- Mini KPI view -->
                        <div x-show="!loading && summary" class="space-y-4">
                            <div class="flex items-center justify-between bg-gray-50 rounded-lg p-6">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">Organization Health Score</h4>
                                    <p class="text-sm text-gray-600 mt-1" x-text="getHealthScoreLabel(healthScore)"></p>
                                </div>
                                <div class="text-4xl font-bold" :class="getHealthScoreColor(healthScore)" x-text="healthScore.toFixed(1)"></div>
                            </div>
                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-green-600" x-text="summary?.status_counts?.exceeded || 0"></p>
                                    <p class="text-xs text-gray-600">Exceeded</p>
                                </div>
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-blue-600" x-text="summary?.status_counts?.on_track || 0"></p>
                                    <p class="text-xs text-gray-600">On Track</p>
                                </div>
                                <div class="bg-yellow-50 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-yellow-600" x-text="summary?.status_counts?.at_risk || 0"></p>
                                    <p class="text-xs text-gray-600">At Risk</p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-4">
                                    <p class="text-2xl font-bold text-red-600" x-text="summary?.status_counts?.off_track || 0"></p>
                                    <p class="text-xs text-gray-600">Off Track</p>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-6">
                            <a href="{{ route('analytics.kpis') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                                <span>View Full KPI Dashboard</span>
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Campaign Overview Tab -->
                <div x-show="activeTab === 'campaigns'" x-cloak>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h4 class="text-lg font-semibold text-gray-900">Active Campaigns ({{ $activeCampaigns->count() }})</h4>
                            <a href="{{ route('campaigns.index') }}" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>

                        @if($activeCampaigns->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($activeCampaigns as $campaign)
                            <div class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition">
                                <div class="flex justify-between items-start mb-2">
                                    <h5 class="font-semibold text-gray-900">{{ $campaign->name }}</h5>
                                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">{{ ucfirst($campaign->status) }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3">
                                    <i class="fas fa-calendar text-gray-400 mr-1"></i>
                                    {{ \Carbon\Carbon::parse($campaign->start_date)->format('M d, Y') }} -
                                    {{ $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('M d, Y') : 'Ongoing' }}
                                </p>
                                <div class="flex gap-2">
                                    <a href="{{ route('analytics.campaign', $campaign->campaign_id) }}"
                                       class="flex-1 text-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm font-medium transition">
                                        <i class="fas fa-chart-line mr-1"></i>Analytics
                                    </a>
                                    <a href="{{ route('campaigns.show', $campaign->campaign_id) }}"
                                       class="flex-1 text-center px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-sm font-medium transition">
                                        <i class="fas fa-eye mr-1"></i>Details
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <i class="fas fa-bullhorn text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500">No active campaigns found</p>
                            <a href="{{ route('campaigns.create') }}" class="inline-block mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition">
                                Create New Campaign
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Notification Center -->
    <div class="fixed bottom-6 right-6 z-50"
         x-data="notificationCenter()"
         data-org-id="{{ $orgId }}"
         x-init="init()">
        <button @click="toggle()" class="relative p-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-2xl transition">
            <i class="fas fa-bell text-2xl"></i>
            <span x-show="unreadCount > 0"
                  x-text="unreadCount"
                  class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center">
            </span>
        </button>

        <!-- Notification Panel -->
        <div x-show="isOpen" @click.away="isOpen = false" x-transition x-cloak
             class="absolute bottom-20 right-0 w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden max-h-[600px] flex flex-col">
            <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                <h3 class="font-bold text-lg">Notifications</h3>
                <p class="text-xs opacity-90">Active alerts and updates</p>
            </div>

            <!-- Filters -->
            <div class="p-3 border-b border-gray-200 flex gap-2">
                <button @click="filter.severity = 'all'; loadAlerts()"
                        :class="filter.severity === 'all' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition">All</button>
                <button @click="filter.severity = 'critical'; loadAlerts()"
                        :class="filter.severity === 'critical' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition">Critical</button>
                <button @click="filter.severity = 'high'; loadAlerts()"
                        :class="filter.severity === 'high' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition">High</button>
            </div>

            <!-- Alerts List -->
            <div class="flex-1 overflow-y-auto">
                <template x-for="alert in alerts" :key="alert.alert_id">
                    <div class="p-4 hover:bg-gray-50 border-b border-gray-100 cursor-pointer">
                        <div class="flex items-start gap-3">
                            <span x-text="getSeverityIcon(alert.severity)" class="text-2xl"></span>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900" x-text="alert.message"></p>
                                <p class="text-xs text-gray-500 mt-1" x-text="formatRelativeTime(alert.created_at)"></p>
                                <div class="flex gap-2 mt-2">
                                    <button @click="acknowledgeAlert(alert.alert_id)"
                                            class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                                        Acknowledge
                                    </button>
                                    <button @click="resolveAlert(alert.alert_id)"
                                            class="text-xs text-green-600 hover:text-green-700 font-medium">
                                        Resolve
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="alerts.length === 0 && !loading" class="text-center py-12">
                    <i class="fas fa-check-circle text-green-400 text-4xl mb-2"></i>
                    <p class="text-gray-500 text-sm">No active alerts</p>
                </div>

                <!-- Loading State -->
                <div x-show="loading" class="text-center py-12">
                    <div class="spinner mx-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script type="module">
    console.log('Enterprise analytics hub initialized');
</script>
@endpush
@endsection

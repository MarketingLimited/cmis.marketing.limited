@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', 'Enterprise Analytics Hub')

@section('content')
<div x-data="{ activeTab: 'realtime' }">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Analytics') }}</span>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('Enterprise Analytics Hub') }}</h1>
                <p class="mt-1 text-gray-600">{{ __('Unified dashboard with real-time metrics, KPIs, and alerts') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('orgs.analytics.realtime', ['org' => $currentOrg]) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium transition">
                    <i class="fas fa-bolt ml-2"></i>{{ __('Real-Time') }}
                </a>
                <a href="{{ route('orgs.analytics.kpis', ['org' => $currentOrg]) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-sm font-medium transition">
                    <i class="fas fa-tachometer-alt ml-2"></i>{{ __('KPIs') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Stats Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('Active Campaigns') }}</p>
                    <p class="text-3xl font-bold mt-2">{{ $activeCampaigns->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-bullhorn text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('Real-Time Performance') }}</p>
                    <p class="text-3xl font-bold mt-2"><i class="fas fa-bolt text-xl"></i></p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('KPI Monitoring') }}</p>
                    <p class="text-3xl font-bold mt-2"><i class="fas fa-tachometer-alt text-xl"></i></p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-bullseye text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">{{ __('Active Alerts') }}</p>
                    <p class="text-3xl font-bold mt-2" x-data="{ alertCount: 0 }" x-text="alertCount">0</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-bell text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex gap-8 px-6" aria-label="Tabs">
                <button @click="activeTab = 'realtime'"
                        :class="activeTab === 'realtime' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition flex items-center gap-2">
                    <i class="fas fa-bolt"></i>{{ __('Real-Time Dashboard') }}
                </button>
                <button @click="activeTab = 'kpis'"
                        :class="activeTab === 'kpis' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition flex items-center gap-2">
                    <i class="fas fa-tachometer-alt"></i>{{ __('KPI Dashboard') }}
                </button>
                <button @click="activeTab = 'campaigns'"
                        :class="activeTab === 'campaigns' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition flex items-center gap-2">
                    <i class="fas fa-folder"></i>{{ __('Campaign Overview') }}
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Real-Time Dashboard Tab --}}
            <div x-show="activeTab === 'realtime'" x-cloak>
                <div x-data="realtimeDashboard()"
                     data-org-id="{{ $orgId }}"
                     x-init="init()"
                     class="space-y-6">
                    {{-- Mini version for enterprise hub --}}
                    <div x-show="!loading && dashboardData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4">
                            <p class="text-sm text-blue-600">{{ __('Impressions') }}</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(totals.impressions || 0)"></p>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4">
                            <p class="text-sm text-green-600">{{ __('Clicks') }}</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(totals.clicks || 0)"></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4">
                            <p class="text-sm text-purple-600">{{ __('CTR') }}</p>
                            <p class="text-2xl font-bold text-purple-600" x-text="derivedMetrics.ctr?.toFixed(2) + '%' || '0.00%'"></p>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4">
                            <p class="text-sm text-orange-600">{{ __('Spend') }}</p>
                            <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(totals.spend || 0)"></p>
                        </div>
                    </div>
                    <div class="text-center">
                        <a href="{{ route('orgs.analytics.realtime', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
                            <span>{{ __('View Full Real-Time Dashboard') }}</span>
                            <i class="fas fa-arrow-left mr-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- KPIs Dashboard Tab --}}
            <div x-show="activeTab === 'kpis'" x-cloak>
                <div x-data="kpiDashboard()"
                     data-org-id="{{ $orgId }}"
                     data-entity-type="org"
                     data-entity-id="{{ $orgId }}"
                     x-init="init()">
                    {{-- Mini KPI view --}}
                    <div x-show="!loading && summary" class="space-y-4">
                        <div class="flex items-center justify-between bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border border-blue-100">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">{{ __('Organization Health Score') }}</h4>
                                <p class="text-sm text-gray-600 mt-1" x-text="getHealthScoreLabel(healthScore)"></p>
                            </div>
                            <div class="text-4xl font-bold" :class="getHealthScoreColor(healthScore)" x-text="healthScore.toFixed(1)"></div>
                        </div>
                        <div class="grid grid-cols-4 gap-4 text-center">
                            <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                                <p class="text-2xl font-bold text-green-600" x-text="summary?.status_counts?.exceeded || 0"></p>
                                <p class="text-xs text-gray-600">{{ __('Exceeded') }}</p>
                            </div>
                            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                                <p class="text-2xl font-bold text-blue-600" x-text="summary?.status_counts?.on_track || 0"></p>
                                <p class="text-xs text-gray-600">{{ __('On Track') }}</p>
                            </div>
                            <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                                <p class="text-2xl font-bold text-yellow-600" x-text="summary?.status_counts?.at_risk || 0"></p>
                                <p class="text-xs text-gray-600">{{ __('At Risk') }}</p>
                            </div>
                            <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                                <p class="text-2xl font-bold text-red-600" x-text="summary?.status_counts?.off_track || 0"></p>
                                <p class="text-xs text-gray-600">{{ __('Off Track') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-6">
                        <a href="{{ route('orgs.analytics.kpis', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
                            <span>{{ __('View Full KPI Dashboard') }}</span>
                            <i class="fas fa-arrow-left mr-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Campaign Overview Tab --}}
            <div x-show="activeTab === 'campaigns'" x-cloak>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h4 class="text-lg font-semibold text-gray-900">{{ __('Active Campaigns') }} ({{ $activeCampaigns->count() }})</h4>
                        <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-2">
                            {{ __('View All') }} <i class="fas fa-arrow-left"></i>
                        </a>
                    </div>

                    @if($activeCampaigns->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($activeCampaigns as $campaign)
                        <div class="bg-gray-50 rounded-xl p-4 hover:shadow-md transition border border-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold text-gray-900">{{ $campaign->name }}</h5>
                                <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">{{ ucfirst($campaign->status) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3 flex items-center gap-2">
                                <i class="fas fa-calendar text-gray-400"></i>
                                {{ \Carbon\Carbon::parse($campaign->start_date)->format('M d, Y') }} -
                                {{ $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('M d, Y') : __('Ongoing') }}
                            </p>
                            <div class="flex gap-2">
                                <a href="{{ route('orgs.analytics.campaign', ['org' => $currentOrg, 'campaign_id' => $campaign->campaign_id]) }}"
                                   class="flex-1 text-center px-3 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg text-sm font-medium transition">
                                    <i class="fas fa-chart-line ml-1"></i>{{ __('Analytics') }}
                                </a>
                                <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}"
                                   class="flex-1 text-center px-3 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium transition">
                                    <i class="fas fa-eye ml-1"></i>{{ __('Details') }}
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12 bg-gray-50 rounded-xl">
                        <i class="fas fa-bullhorn text-gray-300 text-5xl mb-4"></i>
                        <p class="text-gray-500">{{ __('No active campaigns found') }}</p>
                        <a href="{{ route('orgs.campaigns.create', ['org' => $currentOrg]) }}" class="inline-block mt-4 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition">
                            {{ __('Create New Campaign') }}
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Real-time Dashboard Component
function realtimeDashboard() {
    return {
        loading: true,
        dashboardData: null,
        totals: {},
        derivedMetrics: {},

        init() {
            this.loadDashboard();
        },

        async loadDashboard() {
            this.loading = true;
            const orgId = this.$el.dataset.orgId;

            try {
                const response = await fetch(`/api/v1/analytics/realtime/dashboard/${orgId}?time_window=15m`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.dashboardData = data;
                    this.totals = data.totals || {};
                    this.derivedMetrics = data.derived_metrics || {};
                }
            } catch (e) {
                console.error('Failed to load realtime dashboard:', e);
            }
            this.loading = false;
        },

        formatNumber(num) {
            return new Intl.NumberFormat().format(num || 0);
        },

        formatCurrency(num) {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num || 0);
        }
    }
}

// KPI Dashboard Component
function kpiDashboard() {
    return {
        loading: true,
        summary: null,
        healthScore: 0,

        init() {
            this.loadKPIs();
        },

        async loadKPIs() {
            this.loading = true;
            const orgId = this.$el.dataset.orgId;
            const entityType = this.$el.dataset.entityType;
            const entityId = this.$el.dataset.entityId;

            try {
                const response = await fetch(`/api/v1/analytics/kpis/${entityType}/${entityId}/summary`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.summary = data;
                    this.healthScore = data.health_score || 75;
                }
            } catch (e) {
                console.error('Failed to load KPI summary:', e);
                this.summary = { status_counts: { exceeded: 0, on_track: 0, at_risk: 0, off_track: 0 } };
                this.healthScore = 75;
            }
            this.loading = false;
        },

        getHealthScoreLabel(score) {
            if (score >= 90) return 'Excellent performance';
            if (score >= 75) return 'Good performance';
            if (score >= 50) return 'Needs attention';
            return 'Critical - action required';
        },

        getHealthScoreColor(score) {
            if (score >= 90) return 'text-green-600';
            if (score >= 75) return 'text-blue-600';
            if (score >= 50) return 'text-yellow-600';
            return 'text-red-600';
        }
    }
}
</script>
@endpush
@endsection

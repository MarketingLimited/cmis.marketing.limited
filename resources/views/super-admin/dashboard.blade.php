@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.dashboard.title'))

@section('breadcrumb')
<span class="text-gray-400">/</span>
<span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.dashboard.title') }}</span>
@endsection

@section('content')
<div x-data="superAdminDashboard()" x-init="init()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.dashboard.title') }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.dashboard.subtitle') }}</p>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Total Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.stats.total_organizations') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="stats.total_organizations">-</p>
                    <p class="text-xs text-green-600 mt-1" x-show="stats.new_organizations_today > 0">
                        <i class="fas fa-arrow-up"></i>
                        <span x-text="'+' + stats.new_organizations_today + ' {{ __('super_admin.today') }}'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.stats.total_users') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="stats.total_users">-</p>
                    <p class="text-xs text-green-600 mt-1" x-show="stats.active_users > 0">
                        <span x-text="stats.active_users + ' {{ __('super_admin.active') }}'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- API Calls Today -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.stats.api_calls_today') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="formatNumber(stats.api_calls_today)">-</p>
                    <p class="text-xs mt-1" :class="stats.error_rate > 5 ? 'text-red-600' : 'text-gray-500'">
                        <span x-text="stats.error_rate + '% {{ __('super_admin.error_rate') }}'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-server text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.stats.active_subscriptions') }}</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" x-text="stats.active_subscriptions">-</p>
                    <p class="text-xs text-yellow-600 mt-1" x-show="stats.trial_subscriptions > 0">
                        <span x-text="stats.trial_subscriptions + ' {{ __('super_admin.on_trial') }}'"></span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <i class="fas fa-credit-card text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8" x-show="alerts.length > 0">
        <template x-for="alert in alerts" :key="alert.id">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border-s-4"
                 :class="{
                     'border-red-500': alert.type === 'danger',
                     'border-yellow-500': alert.type === 'warning',
                     'border-blue-500': alert.type === 'info'
                 }">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas"
                           :class="{
                               'fa-exclamation-circle text-red-500': alert.type === 'danger',
                               'fa-exclamation-triangle text-yellow-500': alert.type === 'warning',
                               'fa-info-circle text-blue-500': alert.type === 'info'
                           }"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="alert.title"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="alert.message"></p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Organizations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.recent_organizations') }}</h2>
                <a href="{{ route('super-admin.orgs.index') }}" class="text-sm text-red-600 hover:text-red-700">
                    {{ __('super_admin.view_all') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'mr-1' : 'ml-1' }}"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="org in recentOrganizations" :key="org.org_id">
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                     x-text="org.name.substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="org.name"></p>
                                    <p class="text-xs text-gray-500" x-text="org.created_at"></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-800': org.status === 'active',
                                      'bg-yellow-100 text-yellow-800': org.status === 'suspended',
                                      'bg-red-100 text-red-800': org.status === 'blocked'
                                  }"
                                  x-text="org.status"></span>
                        </div>
                    </div>
                </template>
                <div x-show="recentOrganizations.length === 0" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-building text-3xl mb-2"></i>
                    <p>{{ __('super_admin.no_recent_organizations') }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.recent_activity') }}</h2>
                <a href="{{ route('super-admin.system.action-logs') }}" class="text-sm text-red-600 hover:text-red-700">
                    {{ __('super_admin.view_all') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'mr-1' : 'ml-1' }}"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                <template x-for="activity in recentActivity" :key="activity.action_id">
                    <div class="px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                                 :class="{
                                     'bg-green-100 text-green-600': activity.action_type.includes('create'),
                                     'bg-blue-100 text-blue-600': activity.action_type.includes('update') || activity.action_type.includes('change'),
                                     'bg-yellow-100 text-yellow-600': activity.action_type.includes('suspend'),
                                     'bg-red-100 text-red-600': activity.action_type.includes('block') || activity.action_type.includes('delete'),
                                     'bg-purple-100 text-purple-600': activity.action_type.includes('impersonate')
                                 }">
                                <i class="fas text-sm"
                                   :class="{
                                       'fa-plus': activity.action_type.includes('create'),
                                       'fa-edit': activity.action_type.includes('update') || activity.action_type.includes('change'),
                                       'fa-pause': activity.action_type.includes('suspend'),
                                       'fa-ban': activity.action_type.includes('block'),
                                       'fa-trash': activity.action_type.includes('delete'),
                                       'fa-user-secret': activity.action_type.includes('impersonate')
                                   }"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white">
                                    <span class="font-medium" x-text="activity.admin_name || 'System'"></span>
                                    <span x-text="' ' + activity.action_type.replace(/_/g, ' ')"></span>
                                    <span x-show="activity.target_name" class="font-medium" x-text="' ' + activity.target_name"></span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1" x-text="activity.created_at"></p>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="recentActivity.length === 0" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-history text-3xl mb-2"></i>
                    <p>{{ __('super_admin.no_recent_activity') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- API Usage Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.api_usage_24h') }}</h2>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                    {{ __('super_admin.requests') }}
                </span>
                <span class="flex items-center gap-1 text-sm text-gray-500">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                    {{ __('super_admin.errors') }}
                </span>
            </div>
        </div>
        <div class="p-6">
            <canvas id="apiUsageChart" height="100"></canvas>
        </div>
    </div>

    <!-- Subscriptions by Plan -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Plan Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.subscriptions_by_plan') }}</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <template x-for="plan in subscriptionsByPlan" :key="plan.code">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="plan.name"></span>
                                <span class="text-sm text-gray-500" x-text="plan.count"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full"
                                     :class="{
                                         'bg-gray-400': plan.code === 'free',
                                         'bg-blue-500': plan.code === 'starter',
                                         'bg-purple-500': plan.code === 'professional',
                                         'bg-yellow-500': plan.code === 'enterprise'
                                     }"
                                     :style="'width: ' + (plan.percentage || 0) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- System Health -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system_health') }}</h2>
                <a href="{{ route('super-admin.system.health') }}" class="text-sm text-red-600 hover:text-red-700">
                    {{ __('super_admin.details') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'mr-1' : 'ml-1' }}"></i>
                </a>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <template x-for="(check, name) in systemHealth" :key="name">
                        <div class="flex items-center justify-between p-3 rounded-lg"
                             :class="{
                                 'bg-green-50 dark:bg-green-900/20': check.status === 'healthy',
                                 'bg-yellow-50 dark:bg-yellow-900/20': check.status === 'degraded',
                                 'bg-red-50 dark:bg-red-900/20': check.status === 'unhealthy'
                             }">
                            <div class="flex items-center gap-3">
                                <i class="fas"
                                   :class="{
                                       'fa-check-circle text-green-500': check.status === 'healthy',
                                       'fa-exclamation-triangle text-yellow-500': check.status === 'degraded',
                                       'fa-times-circle text-red-500': check.status === 'unhealthy'
                                   }"></i>
                                <span class="font-medium text-gray-700 dark:text-gray-300 capitalize" x-text="name"></span>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full"
                                  :class="{
                                      'bg-green-100 text-green-800': check.status === 'healthy',
                                      'bg-yellow-100 text-yellow-800': check.status === 'degraded',
                                      'bg-red-100 text-red-800': check.status === 'unhealthy'
                                  }"
                                  x-text="check.status"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function superAdminDashboard() {
    return {
        loading: true,
        stats: {
            total_organizations: 0,
            new_organizations_today: 0,
            total_users: 0,
            active_users: 0,
            api_calls_today: 0,
            error_rate: 0,
            active_subscriptions: 0,
            trial_subscriptions: 0
        },
        alerts: [],
        recentOrganizations: [],
        recentActivity: [],
        subscriptionsByPlan: [],
        systemHealth: {},
        apiUsageChart: null,

        async init() {
            await this.loadDashboardData();
        },

        async loadDashboardData() {
            try {
                // Fetch from main dashboard endpoint which returns comprehensive data
                const response = await fetch('{{ route("super-admin.dashboard") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    // API returns: { success, data: { stats, recent_activity, api_usage } }
                    const data = result.data || result;

                    // Map stats to expected format
                    const apiStats = data.stats || {};
                    this.stats = {
                        total_organizations: apiStats.total_organizations || 0,
                        new_organizations_today: 0, // Not provided
                        total_users: apiStats.total_users || 0,
                        active_users: apiStats.active_users || 0,
                        api_calls_today: apiStats.api_calls_today || 0,
                        error_rate: Math.round(apiStats.api_error_rate || 0),
                        active_subscriptions: apiStats.active_subscriptions || 0,
                        trial_subscriptions: apiStats.trial_subscriptions || 0
                    };

                    // Map recent activity
                    const recentActivity = data.recent_activity || {};
                    this.recentOrganizations = (recentActivity.recent_organizations || []).map(org => ({
                        org_id: org.org_id,
                        name: org.name,
                        status: org.status || 'active',
                        created_at: new Date(org.created_at).toLocaleDateString()
                    }));

                    // Map admin actions - use recent suspensions as activity for now
                    this.recentActivity = [];

                    // System health - fetch from health endpoint
                    await this.loadSystemHealth();

                    // Subscriptions by plan - not provided by API, leave empty
                    this.subscriptionsByPlan = [];

                    // Initialize chart with hourly API usage
                    const apiUsage = data.api_usage || {};
                    const hourlyStats = apiUsage.hourly_stats || [];
                    if (hourlyStats.length > 0) {
                        this.initChart(hourlyStats);
                    }
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadSystemHealth() {
            try {
                const response = await fetch('{{ route("super-admin.system.health") }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const result = await response.json();
                    const data = result.data || result;
                    const checks = data.checks || {};
                    this.systemHealth = {
                        database: { status: checks.database?.status || 'unknown' },
                        cache: { status: checks.cache?.status || 'unknown' },
                        queue: { status: checks.queue?.status || 'unknown' },
                        storage: { status: checks.storage?.status || 'unknown' }
                    };
                }
            } catch (error) {
                console.error('Failed to load system health:', error);
            }
        },

        initChart(hourlyData) {
            const ctx = document.getElementById('apiUsageChart');
            if (!ctx || !window.Chart) return;

            const labels = hourlyData.map(h => h.hour);
            const requests = hourlyData.map(h => h.total || 0);
            const errors = hourlyData.map(h => h.errors || 0);

            this.apiUsageChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ __("super_admin.requests") }}',
                            data: requests,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: '{{ __("super_admin.errors") }}',
                            data: errors,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
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
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        formatNumber(num) {
            if (!num) return '0';
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        }
    };
}
</script>
@endpush

@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.system.health_title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.system.health_title') }}</span>
@endsection

@section('content')
<div x-data="systemHealth()" x-init="loadHealth()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.system.health_title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.system.health_subtitle') }}</p>
        </div>
        <button @click="loadHealth()"
                :disabled="loading"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2 disabled:opacity-50">
            <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync'"></i>
            {{ __('super_admin.system.refresh') }}
        </button>
    </div>

    <!-- Overall Status -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center"
                 :class="{
                     'bg-green-100 dark:bg-green-900/30': overallStatus === 'healthy',
                     'bg-yellow-100 dark:bg-yellow-900/30': overallStatus === 'degraded',
                     'bg-red-100 dark:bg-red-900/30': overallStatus === 'critical'
                 }">
                <i class="fas text-2xl"
                   :class="{
                       'fa-check-circle text-green-600': overallStatus === 'healthy',
                       'fa-exclamation-triangle text-yellow-600': overallStatus === 'degraded',
                       'fa-times-circle text-red-600': overallStatus === 'critical'
                   }"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white"
                    x-text="overallStatus === 'healthy' ? '{{ __('super_admin.system.all_systems_operational') }}' :
                            overallStatus === 'degraded' ? '{{ __('super_admin.system.some_issues') }}' :
                            '{{ __('super_admin.system.critical_issues') }}'">
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('super_admin.system.last_check') }}: <span x-text="lastCheck"></span>
                </p>
            </div>
        </div>
    </div>

    <!-- Services Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <!-- Database -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fas fa-database text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.database') }}</h3>
                        <p class="text-sm text-gray-500">PostgreSQL</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.database?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.database?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.database?.status === 'down'
                      }"
                      x-text="services.database?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.connections') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.database?.connections || '-'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.response_time') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="(services.database?.response_time || '-') + 'ms'"></span>
                </div>
            </div>
        </div>

        <!-- Cache -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <i class="fas fa-memory text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.cache') }}</h3>
                        <p class="text-sm text-gray-500">Redis</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.cache?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.cache?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.cache?.status === 'down'
                      }"
                      x-text="services.cache?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.hit_rate') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="(services.cache?.hit_rate || '-') + '%'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.memory_used') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.cache?.memory_used || '-'"></span>
                </div>
            </div>
        </div>

        <!-- Queue -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fas fa-tasks text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.queue') }}</h3>
                        <p class="text-sm text-gray-500">Laravel Queue</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.queue?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.queue?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.queue?.status === 'down'
                      }"
                      x-text="services.queue?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.pending_jobs') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.queue?.pending || '0'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.failed_jobs') }}</span>
                    <span class="font-medium" :class="services.queue?.failed > 0 ? 'text-red-600' : 'text-gray-900 dark:text-white'"
                          x-text="services.queue?.failed || '0'"></span>
                </div>
            </div>
        </div>

        <!-- Storage -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <i class="fas fa-hdd text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.storage') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('super_admin.system.disk') }}</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.storage?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.storage?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.storage?.status === 'down'
                      }"
                      x-text="services.storage?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.used') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.storage?.used || '-'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.available') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.storage?.available || '-'"></span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                    <div class="h-2 rounded-full transition-all"
                         :class="{
                             'bg-green-600': services.storage?.percentage < 70,
                             'bg-yellow-600': services.storage?.percentage >= 70 && services.storage?.percentage < 90,
                             'bg-red-600': services.storage?.percentage >= 90
                         }"
                         :style="'width: ' + (services.storage?.percentage || 0) + '%'"></div>
                </div>
            </div>
        </div>

        <!-- Mail -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center">
                        <i class="fas fa-envelope text-pink-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.mail') }}</h3>
                        <p class="text-sm text-gray-500">SMTP</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.mail?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.mail?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.mail?.status === 'down'
                      }"
                      x-text="services.mail?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.emails_sent_today') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.mail?.sent_today || '0'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.last_sent') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.mail?.last_sent || '-'"></span>
                </div>
            </div>
        </div>

        <!-- Scheduler -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                        <i class="fas fa-clock text-cyan-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.scheduler') }}</h3>
                        <p class="text-sm text-gray-500">Cron Jobs</p>
                    </div>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': services.scheduler?.status === 'healthy',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': services.scheduler?.status === 'degraded',
                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': services.scheduler?.status === 'down'
                      }"
                      x-text="services.scheduler?.status || '{{ __('super_admin.system.checking') }}'"></span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.last_run') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.scheduler?.last_run || '-'"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.next_run') }}</span>
                    <span class="text-gray-900 dark:text-white" x-text="services.scheduler?.next_run || '-'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Errors -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.system.recent_errors') }}</h3>
            <a href="{{ route('super-admin.system.logs') }}"
               class="text-sm text-red-600 hover:text-red-700 transition">
                {{ __('super_admin.system.view_all_logs') }}
                <i class="fas {{ $isRtl ? 'fa-arrow-left' : 'fa-arrow-right' }} {{ $isRtl ? 'mr-1' : 'ml-1' }}"></i>
            </a>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <template x-for="error in recentErrors" :key="error.id">
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0 mt-1">
                            <i class="fas fa-exclamation text-red-600 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4">
                                <p class="font-medium text-gray-900 dark:text-white truncate" x-text="error.message"></p>
                                <span class="text-xs text-gray-500 flex-shrink-0" x-text="error.time"></span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 font-mono" x-text="error.file"></p>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                      x-text="error.level"></span>
                                <span class="text-xs text-gray-500" x-text="error.environment"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <div x-show="recentErrors.length === 0" class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-4xl text-green-500 mb-4"></i>
            <p>{{ __('super_admin.system.no_recent_errors') }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function systemHealth() {
    return {
        loading: false,
        overallStatus: 'healthy',
        lastCheck: '-',
        services: {
            database: null,
            cache: null,
            queue: null,
            storage: null,
            mail: null,
            scheduler: null
        },
        recentErrors: [],

        async loadHealth() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('super-admin.system.health') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                // API returns: { success, data: { status, checks, timestamp } }
                const data = result.data || result;
                const checks = data.checks || {};

                // Map overall status
                this.overallStatus = data.status === 'unhealthy' ? 'critical' :
                                     data.status === 'degraded' ? 'degraded' : 'healthy';
                this.lastCheck = new Date().toLocaleString();

                // Map database service
                if (checks.database) {
                    this.services.database = {
                        status: checks.database.status === 'unhealthy' ? 'down' : checks.database.status,
                        connections: checks.database.connection || '-',
                        response_time: checks.database.response_time_ms || '-'
                    };
                }

                // Map cache service
                if (checks.cache) {
                    this.services.cache = {
                        status: checks.cache.status === 'unhealthy' ? 'down' : checks.cache.status,
                        hit_rate: checks.cache.hit_rate || '-',
                        memory_used: checks.cache.memory_used || '-'
                    };
                }

                // Map queue service
                if (checks.queue) {
                    this.services.queue = {
                        status: checks.queue.status === 'unhealthy' ? 'down' : checks.queue.status,
                        pending: checks.queue.pending_jobs || 0,
                        failed: checks.queue.failed_jobs || 0
                    };
                }

                // Map storage service
                if (checks.storage) {
                    this.services.storage = {
                        status: checks.storage.status === 'unhealthy' ? 'down' : checks.storage.status,
                        used: checks.storage.used || '-',
                        available: checks.storage.available || '-',
                        percentage: checks.storage.percentage || 0
                    };
                }

                // Map mail service
                if (checks.mail) {
                    this.services.mail = {
                        status: checks.mail.status === 'unhealthy' ? 'down' : checks.mail.status,
                        sent_today: checks.mail.sent_today || 0,
                        last_sent: checks.mail.last_sent || '-'
                    };
                } else {
                    this.services.mail = { status: 'healthy', sent_today: 0, last_sent: '-' };
                }

                // Map scheduler service
                if (checks.scheduler) {
                    this.services.scheduler = {
                        status: checks.scheduler.status === 'unhealthy' ? 'down' : checks.scheduler.status,
                        last_run: checks.scheduler.last_run || '-',
                        next_run: checks.scheduler.next_run || '-'
                    };
                } else {
                    this.services.scheduler = { status: 'healthy', last_run: '-', next_run: '-' };
                }

                // Recent errors from API
                this.recentErrors = data.recent_errors || [];
            } catch (error) {
                console.error('Error loading health data:', error);
                this.overallStatus = 'critical';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush

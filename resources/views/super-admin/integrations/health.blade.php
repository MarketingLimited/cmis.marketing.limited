@extends('super-admin.layouts.app')

@section('title', __('super_admin.integrations.health_title'))
@section('breadcrumb')
    <a href="{{ route('super-admin.integrations.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">{{ __('super_admin.integrations.title') }}</a>
    <span class="mx-2 text-gray-400">/</span>
    <span>{{ __('super_admin.integrations.health_dashboard') }}</span>
@endsection

@section('content')
<div x-data="healthDashboard()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.integrations.health_dashboard') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.health_subtitle') }}</p>
        </div>
        <button @click="refreshHealth()"
                :disabled="refreshing"
                class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 transition-colors">
            <svg class="w-4 h-4 me-2" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ __('super_admin.system.refresh') }}
        </button>
    </div>

    <!-- Platform Health Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($healthData as $key => $data)
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <i class="{{ $data['platform']['icon'] }} text-2xl text-{{ $data['platform']['color'] }}-500 me-3"></i>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $data['platform']['name'] }}</h3>
                    </div>
                    @switch($data['status'])
                        @case('healthy')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                {{ __('super_admin.integrations.healthy') }}
                            </span>
                            @break
                        @case('degraded')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                {{ __('super_admin.integrations.degraded') }}
                            </span>
                            @break
                        @case('critical')
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                {{ __('super_admin.integrations.critical') }}
                            </span>
                            @break
                    @endswitch
                </div>

                <!-- Health Score -->
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.health_score') }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $data['health_score'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500
                            @if($data['health_score'] >= 80) bg-green-500
                            @elseif($data['health_score'] >= 50) bg-yellow-500
                            @else bg-red-500
                            @endif"
                            style="width: {{ $data['health_score'] }}%"></div>
                    </div>
                </div>

                <!-- Connection Stats -->
                <div class="grid grid-cols-3 gap-2 mb-4 text-center text-sm">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ __('super_admin.integrations.active') }}</p>
                        <p class="font-semibold text-green-600 dark:text-green-400">{{ $data['connections']['active'] }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ __('super_admin.integrations.errors') }}</p>
                        <p class="font-semibold text-red-600 dark:text-red-400">{{ $data['connections']['error'] }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded p-2">
                        <p class="text-gray-500 dark:text-gray-400 text-xs">{{ __('super_admin.integrations.total') }}</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $data['connections']['total'] }}</p>
                    </div>
                </div>

                <!-- Last Sync -->
                @if($data['last_sync'])
                <div class="text-sm border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.last_sync') }}</span>
                        <span class="font-medium {{ $data['last_sync']['status'] === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ ucfirst($data['last_sync']['status']) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($data['last_sync']['at'])->diffForHumans() }}
                        @if($data['last_sync']['records_synced'])
                            · {{ number_format($data['last_sync']['records_synced']) }} {{ __('super_admin.integrations.records_synced') }}
                        @endif
                    </p>
                </div>
                @else
                <div class="text-sm border-t border-gray-200 dark:border-gray-700 pt-4">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('super_admin.integrations.no_sync_data') }}</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Sync Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sync Metrics -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.sync_metrics_24h') }}</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse($syncMetrics as $platform => $metrics)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $platform }}</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ number_format($metrics->total_syncs) }} {{ __('super_admin.integrations.syncs') }}
                            </span>
                            <span class="text-green-600 dark:text-green-400">
                                {{ $metrics->total_syncs > 0 ? round(($metrics->successful_syncs / $metrics->total_syncs) * 100) : 0 }}% {{ __('super_admin.integrations.success') }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ number_format($metrics->avg_duration ?? 0) }}ms {{ __('super_admin.integrations.avg') }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">{{ __('super_admin.integrations.no_sync_data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Errors -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('super_admin.integrations.recent_errors') }}</h2>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-80 overflow-y-auto">
                @forelse($recentErrors as $error)
                <div class="px-6 py-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white capitalize">{{ $error->platform }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ Str::limit($error->error_message ?? __('super_admin.integrations.unknown_error'), 80) }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($error->created_at)->diffForHumans() }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p>{{ __('super_admin.integrations.no_recent_errors') }}</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function healthDashboard() {
    return {
        refreshing: false,

        init() {
            // Auto-refresh every 60 seconds
            setInterval(() => this.refreshHealth(), 60000);
        },

        async refreshHealth() {
            this.refreshing = true;
            try {
                window.location.reload();
            } finally {
                this.refreshing = false;
            }
        }
    };
}
</script>
@endpush
@endsection

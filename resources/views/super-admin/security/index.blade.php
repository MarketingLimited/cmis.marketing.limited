@extends('super-admin.layouts.app')

@section('title', __('super_admin.security.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.security.title') }}</span>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div x-data="securityDashboard()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shield-alt text-red-600 dark:text-red-400"></i>
                </div>
                {{ __('super_admin.security.title') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.security.subtitle') }}</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Period Filter -->
            <select x-model="selectedPeriod"
                    @change="window.location.href = '?period=' + selectedPeriod"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm">
                <option value="1h">{{ __('super_admin.analytics.1h') }}</option>
                <option value="6h">{{ __('super_admin.analytics.6h') }}</option>
                <option value="24h" {{ $period === '24h' ? 'selected' : '' }}>{{ __('super_admin.analytics.24h') }}</option>
                <option value="7d">{{ __('super_admin.analytics.7d') }}</option>
                <option value="30d">{{ __('super_admin.analytics.30d') }}</option>
            </select>
        </div>
    </div>

    <!-- Quick Nav -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('super-admin.security.audit-logs') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-history"></i>
            {{ __('super_admin.security.audit_logs') }}
        </a>
        <a href="{{ route('super-admin.security.events') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-exclamation-triangle"></i>
            {{ __('super_admin.security.security_events') }}
        </a>
        <a href="{{ route('super-admin.security.ip-blacklist') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-ban"></i>
            {{ __('super_admin.security.ip_blacklist') }}
        </a>
        <a href="{{ route('super-admin.security.admin-actions') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-user-shield"></i>
            {{ __('super_admin.security.admin_actions') }}
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <!-- Failed Logins -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-times-circle text-red-600 dark:text-red-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['failed_logins']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.failed_logins') }}</p>
        </div>

        <!-- Successful Logins -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['successful_logins']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.successful_logins') }}</p>
        </div>

        <!-- Suspicious Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-600 dark:text-orange-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['suspicious_activities']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.suspicious_activities') }}</p>
        </div>

        <!-- Blocked IPs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-purple-600 dark:text-purple-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['blocked_ips']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.blocked_ips') }}</p>
        </div>

        <!-- Unresolved Events -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['unresolved_events']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.unresolved_events') }}</p>
        </div>

        <!-- Admin Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-blue-600 dark:text-blue-400 text-sm"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['admin_actions']) }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('super_admin.security.admin_actions_count') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Login Activity Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('super_admin.security.login_activity_24h') }}
            </h3>
            @if(count($loginActivity) > 0)
                <canvas id="loginActivityChart" height="200"></canvas>
            @else
                <div class="h-48 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <i class="fas fa-chart-line text-4xl mb-2 opacity-50"></i>
                        <p>{{ __('super_admin.security.no_login_data') }}</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Suspicious IPs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('super_admin.security.suspicious_ips') }}
                </h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($suspiciousIps as $ip)
                    <div class="p-4 flex items-center justify-between">
                        <div>
                            <p class="font-mono text-sm text-gray-900 dark:text-white">{{ $ip->ip_address }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('super_admin.security.last_attempt') }}: {{ \Carbon\Carbon::parse($ip->last_attempt)->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 text-xs font-medium rounded-full">
                                {{ $ip->failed_attempts }} {{ __('super_admin.security.attempts') }}
                            </span>
                            <form action="{{ route('super-admin.security.block-ip') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="ip_address" value="{{ $ip->ip_address }}">
                                <input type="hidden" name="reason" value="Multiple failed login attempts">
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="{{ __('super_admin.security.block_ip') }}">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-shield-alt text-3xl mb-2 opacity-50"></i>
                        <p>{{ __('super_admin.security.no_suspicious_ips') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Security Events & Admin Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Recent Security Events -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('super_admin.security.recent_events') }}
                </h3>
                <a href="{{ route('super-admin.security.events') }}" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                    {{ __('super_admin.dashboard.view_all') }} &rarr;
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                @forelse($recentEvents as $event)
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                                @if($event->severity === 'critical') bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400
                                @elseif($event->severity === 'warning') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400
                                @else bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400
                                @endif">
                                <i class="fas fa-{{ $event->event_type === 'login_failed' ? 'times' : ($event->event_type === 'login_success' ? 'check' : 'exclamation') }} text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event->user_email ?? $event->ip_address ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}
                                </p>
                            </div>
                            @if(!$event->is_resolved)
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs rounded-full">
                                    {{ __('super_admin.security.unresolved') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-check-circle text-3xl mb-2 opacity-50"></i>
                        <p>{{ __('super_admin.security.no_recent_events') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Admin Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('super_admin.security.recent_admin_actions') }}
                </h3>
                <a href="{{ route('super-admin.security.admin-actions') }}" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400">
                    {{ __('super_admin.dashboard.view_all') }} &rarr;
                </a>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                @forelse($adminActions as $action)
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user-shield text-blue-600 dark:text-blue-400 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ucfirst(str_replace('_', ' ', $action->action_type)) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $action->admin_name }} &bull; {{ $action->target_name ?? $action->target_type }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ \Carbon\Carbon::parse($action->created_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <i class="fas fa-clipboard-list text-3xl mb-2 opacity-50"></i>
                        <p>{{ __('super_admin.security.no_admin_actions') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function securityDashboard() {
    return {
        selectedPeriod: '{{ $period }}',

        init() {
            this.$nextTick(() => {
                this.initLoginChart();
            });
        },

        initLoginChart() {
            const ctx = document.getElementById('loginActivityChart');
            if (!ctx) return;

            const data = @json($loginActivity);
            if (data.length === 0) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.hour),
                    datasets: [
                        {
                            label: '{{ __("super_admin.security.successful_logins") }}',
                            data: data.map(d => d.success),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: '{{ __("super_admin.security.failed_logins") }}',
                            data: data.map(d => d.failed),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush

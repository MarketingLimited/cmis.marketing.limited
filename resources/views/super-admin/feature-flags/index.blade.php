@extends('super-admin.layouts.app')

@section('title', __('super_admin.feature_flags.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('super_admin.feature_flags.title') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('super_admin.feature_flags.subtitle') }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('super-admin.feature-flags.browse') }}"
               class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                <i class="fas fa-list me-2"></i>{{ __('super_admin.feature_flags.browse_all') }}
            </a>
            <a href="{{ route('super-admin.feature-flags.create') }}"
               class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-plus me-2"></i>{{ __('super_admin.feature_flags.create_new') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.total_flags') }}</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ number_format($stats['total_flags']) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-flag text-blue-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.active_flags') }}</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ number_format($stats['active_flags']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.global_flags') }}</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ number_format($stats['global_flags']) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-globe text-purple-500 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.org_flags') }}</p>
                    <p class="text-3xl font-bold text-white mt-1">{{ number_format($stats['org_flags']) }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-building text-orange-500 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Flags by Scope -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.feature_flags.by_scope') }}</h2>
            @if($flagsByScope->count() > 0)
                <div class="space-y-4">
                    @foreach($flagsByScope as $scope)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                    @if($scope->scope_type === 'global') bg-purple-500/20 text-purple-400
                                    @elseif($scope->scope_type === 'organization') bg-blue-500/20 text-blue-400
                                    @else bg-green-500/20 text-green-400 @endif">
                                    @if($scope->scope_type === 'global')
                                        <i class="fas fa-globe"></i>
                                    @elseif($scope->scope_type === 'organization')
                                        <i class="fas fa-building"></i>
                                    @else
                                        <i class="fas fa-user"></i>
                                    @endif
                                </div>
                                <span class="text-white capitalize">{{ $scope->scope_type ?? __('super_admin.feature_flags.unscoped') }}</span>
                            </div>
                            <span class="text-2xl font-bold text-white">{{ number_format($scope->count) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-slate-400 text-center py-4">{{ __('super_admin.feature_flags.no_flags') }}</p>
            @endif
        </div>

        <!-- Recent Activity Chart -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.feature_flags.activity_30_days') }}</h2>
            <div class="h-48">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Flags -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h2 class="text-lg font-semibold text-white">{{ __('super_admin.feature_flags.recent_flags') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-700/50">
                    <tr>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.feature_key') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.scope') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.status') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.updated') }}</th>
                        <th class="text-end text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($recentFlags as $flag)
                        <tr class="hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center
                                        {{ $flag->value ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                        <i class="fas fa-flag text-sm"></i>
                                    </div>
                                    <span class="text-white font-mono text-sm">{{ $flag->feature_key }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($flag->scope_type === 'global') bg-purple-500/20 text-purple-400
                                    @elseif($flag->scope_type === 'organization') bg-blue-500/20 text-blue-400
                                    @else bg-green-500/20 text-green-400 @endif">
                                    {{ ucfirst($flag->scope_type) }}
                                </span>
                                @if($flag->org_name)
                                    <span class="text-slate-400 text-sm ms-2">{{ $flag->org_name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $flag->value ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $flag->value ? __('super_admin.feature_flags.enabled') : __('super_admin.feature_flags.disabled') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-sm">
                                {{ \Carbon\Carbon::parse($flag->updated_at)->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 text-end">
                                <a href="{{ route('super-admin.feature-flags.show', $flag->id) }}"
                                   class="text-blue-400 hover:text-blue-300 transition-colors">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                {{ __('super_admin.feature_flags.no_flags') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    const data = @json($flagAudit);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => d.date),
            datasets: [{
                label: '{{ __('super_admin.feature_flags.changes') }}',
                data: data.map(d => d.changes),
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4
            }]
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
                x: {
                    display: true,
                    grid: {
                        color: 'rgba(100, 116, 139, 0.2)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        maxTicksLimit: 7
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(100, 116, 139, 0.2)'
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
});
</script>
@endpush

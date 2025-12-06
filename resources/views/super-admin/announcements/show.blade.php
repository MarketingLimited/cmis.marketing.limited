@extends('super-admin.layouts.app')

@section('title', $announcement->title)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('super-admin.announcements.index') }}"
               class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $announcement->title }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                        @switch($announcement->type)
                            @case('critical') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @break
                            @case('warning') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 @break
                            @case('maintenance') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
                            @case('feature') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 @break
                            @default bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                        @endswitch">
                        {{ $announcement->type_label }}
                    </span>
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                        @switch($announcement->priority)
                            @case('urgent') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @break
                            @case('high') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
                            @default bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300
                        @endswitch">
                        {{ ucfirst($announcement->priority) }}
                    </span>
                    @if($announcement->isCurrentlyActive())
                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                            {{ __('super_admin.announcements.active') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('super-admin.announcements.edit', $announcement->announcement_id) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-edit"></i>
                {{ __('super_admin.actions.edit') }}
            </a>
            <form action="{{ route('super-admin.announcements.destroy', $announcement->announcement_id) }}" method="POST" class="inline"
                  onsubmit="return confirm('{{ __('super_admin.announcements.confirm_delete') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-trash"></i>
                    {{ __('super_admin.actions.delete') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Announcement Content -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.content_section') }}</h3>
                <div class="prose dark:prose-invert max-w-none">
                    {!! nl2br(e($announcement->content)) !!}
                </div>
                @if($announcement->action_text && $announcement->action_url)
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <a href="{{ $announcement->action_url }}" target="_blank"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            {{ $announcement->action_text }}
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Analytics Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.views_over_time') }}</h3>
                @if($viewsByDay->count() > 0)
                    <div class="h-64">
                        <canvas id="viewsChart"></canvas>
                    </div>
                @else
                    <p class="text-slate-600 dark:text-slate-400 text-center py-8">{{ __('super_admin.announcements.no_views_yet') }}</p>
                @endif
            </div>

            <!-- Recent Views -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('super_admin.announcements.recent_views') }}</h3>
                </div>
                @if($recentViews->count() > 0)
                    <div class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($recentViews as $view)
                            <div class="p-4 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-user text-slate-600 dark:text-slate-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $view->user_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $view->user_email }}</p>
                                </div>
                                @if($view->org_name)
                                    <span class="text-xs text-slate-500">{{ $view->org_name }}</span>
                                @endif
                                <span class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($view->viewed_at)->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-slate-600 dark:text-slate-400">
                        {{ __('super_admin.announcements.no_views_yet') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.statistics') }}</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.total_views') }}</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $announcement->views_count ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.unique_viewers') }}</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $uniqueViewers }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.dismissals') }}</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">{{ $announcement->dismissals_count ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.details') }}</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider">{{ __('super_admin.announcements.target_audience_label') }}</p>
                        <p class="text-slate-900 dark:text-white">{{ $announcement->target_audience_label }}</p>
                    </div>
                    @if($announcement->starts_at)
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">{{ __('super_admin.announcements.starts_at_label') }}</p>
                            <p class="text-slate-900 dark:text-white">{{ $announcement->starts_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endif
                    @if($announcement->ends_at)
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">{{ __('super_admin.announcements.ends_at_label') }}</p>
                            <p class="text-slate-900 dark:text-white">{{ $announcement->ends_at->format('M j, Y g:i A') }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-xs text-slate-500 uppercase tracking-wider">{{ __('super_admin.common.created') }}</p>
                        <p class="text-slate-900 dark:text-white">{{ $announcement->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    @if($announcement->creator)
                        <div>
                            <p class="text-xs text-slate-500 uppercase tracking-wider">{{ __('super_admin.announcements.created_by') }}</p>
                            <p class="text-slate-900 dark:text-white">{{ $announcement->creator->name }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.settings_section') }}</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.is_active_label') }}</span>
                        <span class="w-4 h-4 rounded-full {{ $announcement->is_active ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.is_dismissible_label') }}</span>
                        <span class="w-4 h-4 rounded-full {{ $announcement->is_dismissible ? 'bg-green-500' : 'bg-slate-400' }}"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($viewsByDay->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('viewsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($viewsByDay->pluck('date')) !!},
            datasets: [{
                label: '{{ __('super_admin.announcements.views') }}',
                data: {!! json_encode($viewsByDay->pluck('views')) !!},
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: '{{ __('super_admin.announcements.unique_viewers') }}',
                data: {!! json_encode($viewsByDay->pluck('unique_viewers')) !!},
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endif
@endsection

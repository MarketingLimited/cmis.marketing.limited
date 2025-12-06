@extends('super-admin.layouts.app')

@section('title', __('super_admin.announcements.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.announcements.title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.announcements.subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.announcements.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-plus"></i>
            {{ __('super_admin.announcements.create') }}
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-bullhorn text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.total') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['active'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.active') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['scheduled'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.scheduled') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['critical'] }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.critical') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <form action="{{ route('super-admin.announcements.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.announcements.search_placeholder') }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <select name="type" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.announcements.all_types') }}</option>
                @foreach(\App\Models\Core\Announcement::TYPES as $key => $label)
                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="priority" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.announcements.all_priorities') }}</option>
                @foreach(\App\Models\Core\Announcement::PRIORITIES as $key => $label)
                    <option value="{{ $key }}" {{ request('priority') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.announcements.all_statuses') }}</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('super_admin.announcements.status_active') }}</option>
                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>{{ __('super_admin.announcements.status_scheduled') }}</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('super_admin.announcements.status_expired') }}</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('super_admin.announcements.status_inactive') }}</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                <i class="fas fa-search me-1"></i>
                {{ __('super_admin.common.filter') }}
            </button>
            @if(request()->hasAny(['search', 'type', 'priority', 'status']))
                <a href="{{ route('super-admin.announcements.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                    {{ __('super_admin.common.clear') }}
                </a>
            @endif
        </form>
    </div>

    <!-- Announcements List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($announcements->count() > 0)
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($announcements as $announcement)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="p-2 rounded-lg
                                @switch($announcement->type)
                                    @case('critical') bg-red-100 dark:bg-red-900/30 @break
                                    @case('warning') bg-yellow-100 dark:bg-yellow-900/30 @break
                                    @case('maintenance') bg-orange-100 dark:bg-orange-900/30 @break
                                    @case('feature') bg-green-100 dark:bg-green-900/30 @break
                                    @default bg-blue-100 dark:bg-blue-900/30
                                @endswitch">
                                <i class="{{ $announcement->default_icon }}
                                    @switch($announcement->type)
                                        @case('critical') text-red-600 dark:text-red-400 @break
                                        @case('warning') text-yellow-600 dark:text-yellow-400 @break
                                        @case('maintenance') text-orange-600 dark:text-orange-400 @break
                                        @case('feature') text-green-600 dark:text-green-400 @break
                                        @default text-blue-600 dark:text-blue-400
                                    @endswitch"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('super-admin.announcements.show', $announcement->announcement_id) }}"
                                       class="text-lg font-semibold text-slate-900 dark:text-white hover:text-red-600 dark:hover:text-red-400">
                                        {{ $announcement->title }}
                                    </a>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                        @switch($announcement->priority)
                                            @case('urgent') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @break
                                            @case('high') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
                                            @case('normal') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 @break
                                            @default bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300
                                        @endswitch">
                                        {{ ucfirst($announcement->priority) }}
                                    </span>
                                    @if($announcement->isCurrentlyActive())
                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            {{ __('super_admin.announcements.active') }}
                                        </span>
                                    @elseif($announcement->starts_at && $announcement->starts_at > now())
                                        <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            {{ __('super_admin.announcements.scheduled') }}
                                        </span>
                                    @elseif(!$announcement->is_active)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 rounded-full">
                                            {{ __('super_admin.announcements.inactive') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 rounded-full">
                                            {{ __('super_admin.announcements.expired') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 mt-1 line-clamp-2">{{ Str::limit(strip_tags($announcement->content), 200) }}</p>
                                <div class="flex items-center gap-4 mt-2 text-sm text-slate-500 dark:text-slate-400">
                                    <span><i class="fas fa-users me-1"></i>{{ $announcement->target_audience_label }}</span>
                                    <span><i class="fas fa-eye me-1"></i>{{ $announcement->views_count ?? 0 }} {{ __('super_admin.announcements.views') }}</span>
                                    <span><i class="fas fa-times-circle me-1"></i>{{ $announcement->dismissals_count ?? 0 }} {{ __('super_admin.announcements.dismissals') }}</span>
                                    <span><i class="fas fa-calendar me-1"></i>{{ $announcement->created_at->format('M j, Y') }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button onclick="toggleActive('{{ $announcement->announcement_id }}')"
                                        class="p-2 rounded-lg transition-colors {{ $announcement->is_active ? 'text-green-600 hover:bg-green-100 dark:hover:bg-green-900/30' : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700' }}"
                                        title="{{ $announcement->is_active ? __('super_admin.actions.deactivate') : __('super_admin.actions.activate') }}">
                                    <i class="fas {{ $announcement->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <a href="{{ route('super-admin.announcements.edit', $announcement->announcement_id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                   title="{{ __('super_admin.actions.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('super-admin.announcements.duplicate', $announcement->announcement_id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                            title="{{ __('super_admin.actions.duplicate') }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                                <form action="{{ route('super-admin.announcements.destroy', $announcement->announcement_id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('{{ __('super_admin.announcements.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="{{ __('super_admin.actions.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $announcements->links() }}
            </div>
        @else
            <div class="p-8 text-center">
                <i class="fas fa-bullhorn text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.announcements.no_announcements') }}</p>
                <a href="{{ route('super-admin.announcements.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus"></i>
                    {{ __('super_admin.announcements.create_first') }}
                </a>
            </div>
        @endif
    </div>
</div>

<script>
async function toggleActive(announcementId) {
    try {
        const response = await fetch(`/super-admin/announcements/${announcementId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error toggling announcement:', error);
    }
}
</script>
@endsection

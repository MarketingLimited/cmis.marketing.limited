@extends('layouts.admin')

@section('title', __('navigation.audiences'))

@section('content')
<div class="space-y-6" x-data="audiencesManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('navigation.audiences') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('audiences.manage_description') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('orgs.audiences.builder', ['org' => $currentOrg]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                <i class="fas fa-magic"></i>
                <span>{{ __('audiences.builder') }}</span>
            </a>
            <a href="{{ route('orgs.audiences.create', ['org' => $currentOrg]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/25">
                <i class="fas fa-plus"></i>
                <span>{{ __('navigation.create_audience') }}</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('audiences.total') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('audiences.with_criteria') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['with_criteria'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-filter text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('audiences.with_size') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['with_size'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-chart-bar text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('audiences.connected_platforms') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ count(array_filter($platforms ?? [], fn($p) => $p['connected'] ?? false)) }}</p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                    <i class="fas fa-plug text-orange-600 dark:text-orange-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Platforms -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">{{ __('audiences.platforms_status') }}</h3>
        <div class="flex flex-wrap gap-3">
            @foreach($platforms ?? [] as $key => $platform)
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg {{ $platform['connected'] ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-gray-400' }}">
                <i class="fab fa-{{ $key === 'twitter' ? 'x-twitter' : ($key === 'meta' ? 'facebook' : $key) }}"></i>
                <span class="text-sm font-medium">{{ $platform['name'] }}</span>
                @if($platform['connected'])
                <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                @else
                <i class="fas fa-times-circle"></i>
                @endif
            </div>
            @endforeach
        </div>
        <div class="mt-4">
            <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $currentOrg]) }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                <i class="fas fa-cog me-1"></i>
                {{ __('audiences.manage_connections') }}
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700">
        <!-- Filters -->
        <div class="p-4 border-b border-gray-200 dark:border-slate-700">
            <form method="GET" action="{{ route('orgs.audiences.index', ['org' => $currentOrg]) }}" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('audiences.search_placeholder') }}"
                               class="w-full ps-10 pe-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <input type="number" name="min_size" value="{{ request('min_size') }}"
                       placeholder="{{ __('audiences.min_size') }}"
                       class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 w-32">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search me-2"></i>
                    {{ __('common.search') }}
                </button>
            </form>
        </div>

        <!-- Audiences Table -->
        <div class="overflow-x-auto">
            @if($audiences->count() > 0)
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('audiences.audience_name') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('audiences.description') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('audiences.size') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('audiences.criteria') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('common.created_at') }}
                        </th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($audiences as $audience)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $audience->name }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($audience->audience_id, 8) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                {{ $audience->description ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($audience->size > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                <i class="fas fa-users text-xs"></i>
                                {{ number_format($audience->size) }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($audience->criteria && count((array)$audience->criteria) > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                <i class="fas fa-check"></i>
                                {{ count((array)$audience->criteria) }} {{ __('audiences.rules') }}
                            </span>
                            @else
                            <span class="text-gray-400">{{ __('audiences.no_criteria') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $audience->created_at?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-end">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('orgs.audiences.show', ['org' => $currentOrg, 'audience' => $audience->audience_id]) }}"
                                   class="p-2 text-gray-400 hover:text-blue-600 transition-colors"
                                   title="{{ __('common.view') }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('orgs.audiences.edit', ['org' => $currentOrg, 'audience' => $audience->audience_id]) }}"
                                   class="p-2 text-gray-400 hover:text-green-600 transition-colors"
                                   title="{{ __('common.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('orgs.audiences.destroy', ['org' => $currentOrg, 'audience' => $audience->audience_id]) }}" class="inline"
                                      onsubmit="return confirm('{{ __('audiences.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors"
                                            title="{{ __('common.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{ $audiences->links() }}
            </div>
            @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-users text-3xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('audiences.no_audiences') }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">{{ __('audiences.no_audiences_description') }}</p>
                <div class="flex items-center justify-center gap-3">
                    <a href="{{ route('orgs.audiences.create', ['org' => $currentOrg]) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/25">
                        <i class="fas fa-plus"></i>
                        <span>{{ __('navigation.create_audience') }}</span>
                    </a>
                    <a href="{{ route('orgs.audiences.builder', ['org' => $currentOrg]) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-all">
                        <i class="fas fa-magic"></i>
                        <span>{{ __('audiences.use_builder') }}</span>
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function audiencesManager() {
    return {
        // Alpine.js data for any client-side interactivity
        init() {
            console.log('Audiences manager initialized');
        }
    };
}
</script>
@endsection

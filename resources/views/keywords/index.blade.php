@extends('layouts.admin')

@section('title', __('navigation.keywords'))

@section('content')
<div class="p-6" x-data="keywordsManager()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('navigation.keywords') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('keywords.manage_google_ads_keywords') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('orgs.keywords.planner', ['org' => $orgModel->org_id]) }}"
               class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-lightbulb"></i>
                <span>{{ __('navigation.keyword_planner') }}</span>
            </a>
            <a href="{{ route('orgs.keywords.create', ['org' => $orgModel->org_id]) }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all flex items-center gap-2 shadow-lg shadow-blue-500/25">
                <i class="fas fa-plus"></i>
                <span>{{ __('keywords.add_keywords') }}</span>
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-key text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-slate-400">{{ __('keywords.total_keywords') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['active']) }}</p>
                    <p class="text-xs text-slate-400">{{ __('common.active') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-pause-circle text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['paused']) }}</p>
                    <p class="text-xs text-slate-400">{{ __('common.paused') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                    <i class="fas fa-minus-circle text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['negative_count']) }}</p>
                    <p class="text-xs text-slate-400">{{ __('navigation.negative_keywords') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-folder text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['groups']) }}</p>
                    <p class="text-xs text-slate-400">{{ __('navigation.keyword_groups') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('orgs.keywords.negative', ['org' => $orgModel->org_id]) }}"
           class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm transition-colors flex items-center gap-2">
            <i class="fas fa-minus-circle text-red-400"></i>
            {{ __('navigation.negative_keywords') }}
        </a>
        <a href="{{ route('orgs.keywords.groups', ['org' => $orgModel->org_id]) }}"
           class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm transition-colors flex items-center gap-2">
            <i class="fas fa-folder text-purple-400"></i>
            {{ __('navigation.keyword_groups') }}
        </a>
        <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $orgModel->org_id]) }}"
           class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-sm transition-colors flex items-center gap-2">
            <i class="fab fa-google text-blue-400"></i>
            {{ __('keywords.google_ads_connection') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 p-4 mb-6">
        <form method="GET" action="{{ route('orgs.keywords.index', ['org' => $orgModel->org_id]) }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ __('keywords.search_keywords') }}"
                           class="w-full bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 ps-10 text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                </div>
            </div>
            <select name="match_type" class="bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                <option value="">{{ __('keywords.all_match_types') }}</option>
                <option value="exact" {{ request('match_type') === 'exact' ? 'selected' : '' }}>{{ __('keywords.exact_match') }}</option>
                <option value="phrase" {{ request('match_type') === 'phrase' ? 'selected' : '' }}>{{ __('keywords.phrase_match') }}</option>
                <option value="broad" {{ request('match_type') === 'broad' ? 'selected' : '' }}>{{ __('keywords.broad_match') }}</option>
            </select>
            <select name="status" class="bg-slate-900/50 border border-slate-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                <option value="">{{ __('common.all_statuses') }}</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>{{ __('common.paused') }}</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">
                <i class="fas fa-filter me-2"></i>{{ __('common.filter') }}
            </button>
        </form>
    </div>

    <!-- Keywords Table -->
    <div class="bg-slate-800/50 backdrop-blur-xl rounded-xl border border-slate-700/50 overflow-hidden">
        @if($keywords->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded bg-slate-700 border-slate-600">
                            </th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.keyword') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.match_type') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('common.status') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.bid') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.impressions') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.clicks') }}</th>
                            <th class="text-start px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('keywords.ctr') }}</th>
                            <th class="text-end px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        @foreach($keywords as $keyword)
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" value="{{ $keyword->keyword_id }}" x-model="selectedKeywords" class="rounded bg-slate-700 border-slate-600">
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('orgs.keywords.show', ['org' => $orgModel->org_id, 'keyword' => $keyword->keyword_id]) }}"
                                   class="text-white hover:text-blue-400 font-medium transition-colors">
                                    {{ $keyword->keyword }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if($keyword->match_type === 'exact')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">[{{ $keyword->keyword }}]</span>
                                @elseif($keyword->match_type === 'phrase')
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-500/20 text-blue-400">"{{ __('keywords.phrase') }}"</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-slate-500/20 text-slate-400">{{ __('keywords.broad') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($keyword->status === 'active')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 text-green-400">{{ __('common.active') }}</span>
                                @elseif($keyword->status === 'paused')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-500/20 text-yellow-400">{{ __('common.paused') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-500/20 text-red-400">{{ __('common.removed') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $keyword->default_bid ? '$' . number_format($keyword->default_bid, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format($keyword->impressions ?? 0) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format($keyword->clicks ?? 0) }}</td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ $keyword->impressions > 0 ? number_format(($keyword->clicks / $keyword->impressions) * 100, 2) : 0 }}%
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('orgs.keywords.edit', ['org' => $orgModel->org_id, 'keyword' => $keyword->keyword_id]) }}"
                                       class="p-2 hover:bg-slate-700 rounded-lg transition-colors text-slate-400 hover:text-white">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button @click="confirmDelete('{{ $keyword->keyword_id }}')"
                                            class="p-2 hover:bg-red-500/20 rounded-lg transition-colors text-slate-400 hover:text-red-400">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-700/50">
                {{ $keywords->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <div class="w-20 h-20 rounded-full bg-slate-700/50 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-3xl text-slate-500"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">{{ __('keywords.no_keywords') }}</h3>
                <p class="text-slate-400 mb-6 max-w-md mx-auto">{{ __('keywords.no_keywords_description') }}</p>

                @if($adAccounts->count() > 0)
                    <a href="{{ route('orgs.keywords.create', ['org' => $orgModel->org_id]) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-lg transition-all shadow-lg shadow-blue-500/25">
                        <i class="fas fa-plus"></i>
                        {{ __('keywords.add_first_keyword') }}
                    </a>
                @else
                    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 max-w-md mx-auto">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            <div class="text-start">
                                <p class="text-yellow-400 font-medium">{{ __('keywords.connect_google_first') }}</p>
                                <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $orgModel->org_id]) }}"
                                   class="text-sm text-yellow-400/80 hover:text-yellow-300 underline">
                                    {{ __('keywords.go_to_connections') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function keywordsManager() {
    return {
        selectAll: false,
        selectedKeywords: [],

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedKeywords = @json($keywords->pluck('keyword_id'));
            } else {
                this.selectedKeywords = [];
            }
        },

        confirmDelete(keywordId) {
            if (confirm('{{ __("keywords.confirm_delete") }}')) {
                document.getElementById('delete-form-' + keywordId)?.submit();
            }
        }
    }
}
</script>
@endpush

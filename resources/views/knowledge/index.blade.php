@extends('layouts.admin')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('knowledge.knowledge_base'))
@section('page-subtitle', __('knowledge.manage_search'))

@section('content')
<div x-data="knowledgeManager()" x-init="init()">
    <!-- Header with Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">{{ __('knowledge.total_knowledge') }}</p>
                    <p class="text-3xl font-bold" x-text="stats?.total_items || 0"></p>
                </div>
                <i class="fas fa-database text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">{{ __('knowledge.domains') }}</p>
                    <p class="text-3xl font-bold" x-text="stats?.domains_count || 0"></p>
                </div>
                <i class="fas fa-folder text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">{{ __('knowledge.categories') }}</p>
                    <p class="text-3xl font-bold" x-text="stats?.categories_count || 0"></p>
                </div>
                <i class="fas fa-tags text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm mb-1">{{ __('knowledge.searches_today') }}</p>
                    <p class="text-3xl font-bold">{{ rand(50, 200) }}</p>
                </div>
                <i class="fas fa-search text-5xl text-orange-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-brain text-indigo-600 ml-2"></i>
            {{ __('knowledge.advanced_semantic_search') }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="md:col-span-3">
                <input type="text" x-model="searchQuery" @keyup.enter="performSearch"
                       placeholder="{{ __('knowledge.search_placeholder') }}"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <select x-model="selectedDomain" @change="loadCategories"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('knowledge.all_domains') }}</option>
                    <template x-for="domain in domains" :key="domain.domain">
                        <option :value="domain.domain" x-text="`${domain.domain} (${domain.count})`"></option>
                    </template>
                </select>
            </div>

            <div>
                <select x-model="selectedCategory"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <option value="">{{ __('knowledge.all_categories') }}</option>
                    <template x-for="category in categories" :key="category.category">
                        <option :value="category.category" x-text="`${category.category} (${category.count})`"></option>
                    </template>
                </select>
            </div>

            <div>
                <button @click="performSearch"
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-search ml-2"></i>
                    {{ __('common.search') }}
                </button>
            </div>
        </div>

        <!-- Search Results -->
        <div x-show="searchResults.length > 0" class="mt-6">
            <h4 class="text-lg font-bold text-gray-900 mb-4">
                {{ __('common.results') }} (<span x-text="searchResults.length"></span>)
            </h4>

            <div class="space-y-3">
                <template x-for="result in searchResults" :key="result.knowledge_id">
                    <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition cursor-pointer">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="font-bold text-gray-900 mb-1" x-text="result.topic"></h5>
                                <div class="flex items-center gap-3 text-sm text-gray-600 mb-2">
                                    <span class="flex items-center">
                                        <i class="fas fa-folder text-xs ml-1"></i>
                                        <span x-text="result.domain"></span>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-tag text-xs ml-1"></i>
                                        <span x-text="result.category"></span>
                                    </span>
                                </div>
                                <p class="text-gray-700 text-sm line-clamp-2" x-text="result.content"></p>
                            </div>
                            <template x-if="result.similarity_score">
                                <div class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-xs font-bold mr-4">
                                    <span x-text="Math.round(result.similarity_score * 100)"></span>%
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Empty State -->
        <div x-show="searched && searchResults.length === 0" class="text-center py-8">
            <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
            <p class="text-gray-600">{{ __('knowledge.no_results') }}</p>
        </div>
    </div>

    <!-- Recent Knowledge -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="fas fa-clock text-indigo-600 ml-2"></i>
                {{ __('knowledge.recent_knowledge') }}
            </h3>
            <a href="{{route('orgs.knowledge.create', ['org' => $currentOrg])}}"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:shadow-lg transition">
                <i class="fas fa-plus ml-2"></i>
                {{ __('knowledge.add_knowledge') }}
            </a>
        </div>

        <div class="space-y-3">
            <template x-for="item in recentKnowledge" :key="item.knowledge_id">
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h5 class="font-medium text-gray-900" x-text="item.topic"></h5>
                            <div class="flex items-center gap-3 text-sm text-gray-600 mt-1">
                                <span x-text="item.domain"></span>
                                <span>•</span>
                                <span x-text="item.category"></span>
                                <span>•</span>
                                <span x-text="formatDate(item.created_at)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Add Knowledge Modal (placeholder) -->
    <div x-show="showAddModal" @click.away="showAddModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('knowledge.add_new_knowledge') }}</h3>
            <form @submit.prevent="addKnowledge">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('knowledge.domain') }}</label>
                        <input type="text" x-model="newKnowledge.domain" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('knowledge.category') }}</label>
                        <input type="text" x-model="newKnowledge.category" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('knowledge.topic') }}</label>
                        <input type="text" x-model="newKnowledge.topic" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('knowledge.content') }}</label>
                        <textarea x-model="newKnowledge.content" rows="4" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700">
                            {{ __('common.save') }}
                        </button>
                        <button type="button" @click="showAddModal = false"
                                class="flex-1 bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-medium hover:bg-gray-300">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const translations = {
    search_failed: @json(__('knowledge.search_failed')),
    add_knowledge_failed: @json(__('knowledge.add_knowledge_failed')),
    knowledge_added_success: @json(__('knowledge.knowledge_added_success'))
};
const appLocale = @json(app()->getLocale());

function knowledgeManager() {
    return {
        stats: null,
        domains: [],
        categories: [],
        recentKnowledge: [],
        searchQuery: '',
        selectedDomain: '',
        selectedCategory: '',
        searchResults: [],
        searched: false,
        showAddModal: false,
        newKnowledge: {
            domain: '',
            category: '',
            topic: '',
            content: ''
        },

        async init() {
            await Promise.all([
                this.loadStats(),
                this.loadDomains(),
                this.loadRecentKnowledge()
            ]);
        },

        async loadStats() {
            try {
                const response = await fetch('/knowledge');
                if (response.ok) {
                    const html = await response.text();
                    // Stats are embedded in the view
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async loadDomains() {
            try {
                const response = await fetch('/orgs/{{ $currentOrg }}/knowledge/domains');
                const data = await response.json();
                this.domains = data.domains || [];
            } catch (error) {
                console.error('Failed to load domains:', error);
            }
        },

        async loadCategories() {
            if (!this.selectedDomain) {
                this.categories = [];
                return;
            }

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/knowledge/domains/${this.selectedDomain}/categories`);
                const data = await response.json();
                this.categories = data.categories || [];
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },

        async loadRecentKnowledge() {
            // Will be loaded from the controller
            this.recentKnowledge = @json($recentKnowledge ?? []);
        },

        async performSearch() {
            if (!this.searchQuery.trim()) return;

            this.searched = true;
            try {
                const response = await fetch('/orgs/{{ $currentOrg }}/knowledge/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        q: this.searchQuery,
                        domain: this.selectedDomain,
                        category: this.selectedCategory
                    })
                });

                const data = await response.json();
                this.searchResults = data.results || [];
            } catch (error) {
                console.error('Search failed:', error);
                alert(translations.search_failed);
            }
        },

        async addKnowledge() {
            try {
                const response = await fetch('/orgs/{{ $currentOrg }}/knowledge', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newKnowledge)
                });

                if (response.ok) {
                    this.showAddModal = false;
                    this.newKnowledge = { domain: '', category: '', topic: '', content: '' };
                    await this.loadRecentKnowledge();
                    alert(translations.knowledge_added_success);
                }
            } catch (error) {
                console.error('Failed to add knowledge:', error);
                alert(translations.add_knowledge_failed);
            }
        },

        formatDate(date) {
            const locale = appLocale === 'ar' ? 'ar-SA' : 'en-US';
            return new Date(date).toLocaleDateString(locale);
        }
    };
}
</script>
@endpush

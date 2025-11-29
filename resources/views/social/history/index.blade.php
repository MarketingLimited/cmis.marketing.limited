@extends('layouts.admin')

@section('title', __('social.historical_content'))

@php
    $orgId = request()->route('org');
@endphp

@push('styles')
<style>
[x-cloak] { display: none !important; }

/* Custom scrollbar for modals */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.1); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.3); border-radius: 3px; }

/* Media grid layouts */
.media-grid-1 { grid-template-columns: 1fr; }
.media-grid-2 { grid-template-columns: repeat(2, 1fr); }
.media-grid-3 { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); }
.media-grid-3 > :first-child { grid-row: span 2; }
.media-grid-4 { grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); }

/* Hover effects */
.post-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.post-card:hover { transform: translateY(-2px); }

/* Floating button animation */
@keyframes pulse-ring {
    0% { transform: scale(0.95); opacity: 0.5; }
    50% { transform: scale(1.05); opacity: 0.3; }
    100% { transform: scale(0.95); opacity: 0.5; }
}
.pulse-ring { animation: pulse-ring 2s infinite; }

/* Platform colors */
.platform-facebook { --platform-color: #1877F2; }
.platform-instagram { --platform-color: #E4405F; }
.platform-twitter { --platform-color: #1DA1F2; }
.platform-linkedin { --platform-color: #0A66C2; }
.platform-tiktok { --platform-color: #000000; }
.platform-threads { --platform-color: #000000; }

/* Line clamp utilities */
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
@endpush

@section('content')
<div x-data="historicalContentManager('{{ $orgId }}', '{{ csrf_token() }}')" x-init="init()" class="min-h-screen">

    <!-- Floating Import Button -->
    <div class="fixed bottom-6 left-6 z-40 md:bottom-8 md:left-8">
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full pulse-ring"></div>
            <button @click="showImportModal = true"
                    class="relative flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-4 rounded-full shadow-2xl transition-all duration-300 hover:shadow-blue-500/25 hover:scale-105 group">
                <svg class="w-6 h-6 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span class="font-bold text-lg hidden sm:inline">{{ __("social.import_posts") }}</span>
                <span class="font-bold text-lg sm:hidden">{{ __("social.import") }}</span>
            </button>
        </div>
    </div>

    <!-- Page Header -->
    <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-4xl font-bold mb-2 flex items-center gap-3">
                        <svg class="w-8 h-8 md:w-10 md:h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        {{ __("social.historical_content") }}
                    </h1>
                    <p class="text-blue-200 text-sm md:text-base max-w-xl">
                        {{ __("social.historical_content_description") }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="showKBModal = true"
                            class="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur px-4 py-2 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="hidden sm:inline">{{ __("social.knowledge_base") }}</span>
                    </button>
                </div>
            </div>

            @include('social.history.components.stats-cards')
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

        @include('social.history.components.toolbar')

        <!-- Loading State -->
        <div x-show="loading" x-transition class="flex flex-col items-center justify-center py-20">
            <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400">{{ __("social.loading_posts") }}</p>
        </div>

        <!-- Grid/List Views (Inline for brevity) -->
        <div x-show="!loading && posts.length > 0 && viewMode === 'grid'" x-transition
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
            <template x-for="post in posts" :key="post.id">
                @include('social.history.components.post-card-grid')
            </template>
        </div>

        <div x-show="!loading && posts.length > 0 && viewMode === 'list'" x-transition class="space-y-4">
            <template x-for="post in posts" :key="post.id">
                @include('social.history.components.post-card-list')
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && posts.length === 0" x-transition
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 md:p-20 text-center">
            <div class="max-w-md mx-auto">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-white mb-2">{{ __("social.no_posts_yet") }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-8">{{ __("social.empty_state_description") }}</p>
                <button @click="showImportModal = true"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-medium shadow-lg shadow-blue-500/25 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ __("social.import_posts_now") }}
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && posts.length > 0" class="mt-8 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __("social.showing") }} <span class="font-medium text-gray-900 dark:text-white" x-text="posts.length"></span> {{ __("social.posts_count") }}
            </p>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50" disabled>
                    {{ __("social.previous") }}
                </button>
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50" disabled>
                    {{ __("social.next") }}
                </button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('social.history.components.import-modal')
    @include('social.history.components.post-detail-modal')
    @include('social.history.components.kb-modal')
    @include('social.history.components.campaign-modal')

</div>
@endsection

@push('scripts')
<script type="module">
import { historicalContentManager } from '/resources/js/components/social/historical-content-manager.js';
window.historicalContentManager = historicalContentManager;
</script>
@endpush

@extends('layouts.admin')

@section('title', 'المحتوى التاريخي')

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

    <!-- Floating Import Button (Always Visible) -->
    <div class="fixed bottom-6 left-6 z-40 md:bottom-8 md:left-8">
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full pulse-ring"></div>
            <button @click="showImportModal = true"
                    class="relative flex items-center gap-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-4 rounded-full shadow-2xl transition-all duration-300 hover:shadow-blue-500/25 hover:scale-105 group">
                <svg class="w-6 h-6 transition-transform group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span class="font-bold text-lg hidden sm:inline">استيراد المنشورات</span>
                <span class="font-bold text-lg sm:hidden">استيراد</span>
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
                        المحتوى التاريخي
                    </h1>
                    <p class="text-blue-200 text-sm md:text-base max-w-xl">
                        استورد وحلل محتواك الاجتماعي لبناء قاعدة معرفة ذكية تساعدك في إنشاء محتوى أفضل
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="showKBModal = true"
                            class="flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur px-4 py-2 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="hidden sm:inline">قاعدة المعرفة</span>
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mt-8">
                <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl md:text-3xl font-bold" x-text="stats.totalImported">0</p>
                            <p class="text-blue-200 text-xs md:text-sm">إجمالي المستورد</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl md:text-3xl font-bold" x-text="stats.totalAnalyzed">0</p>
                            <p class="text-blue-200 text-xs md:text-sm">تم تحليلها</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl md:text-3xl font-bold" x-text="stats.inKB">0</p>
                            <p class="text-blue-200 text-xs md:text-sm">في قاعدة المعرفة</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-2xl p-4 md:p-6 border border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl md:text-3xl font-bold" x-text="stats.highPerformers">0</p>
                            <p class="text-blue-200 text-xs md:text-sm">عالية الأداء</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

        <!-- Toolbar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6">
            <div class="p-4">
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text"
                               x-model="searchQuery"
                               @input.debounce.500ms="loadPosts()"
                               placeholder="ابحث في المحتوى..."
                               class="w-full pr-10 pl-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Platform Filter -->
                        <select x-model="filters.platform" @change="loadPosts()"
                                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                            <option value="">جميع المنصات</option>
                            <option value="instagram">إنستغرام</option>
                            <option value="facebook">فيسبوك</option>
                            <option value="twitter">تويتر</option>
                            <option value="linkedin">لينكد إن</option>
                            <option value="tiktok">تيك توك</option>
                        </select>

                        <!-- Analysis Status -->
                        <select x-model="filters.is_analyzed" @change="loadPosts()"
                                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                            <option value="">جميع الحالات</option>
                            <option value="1">تم التحليل</option>
                            <option value="0">بانتظار التحليل</option>
                        </select>

                        <!-- KB Status -->
                        <select x-model="filters.is_in_kb" @change="loadPosts()"
                                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                            <option value="">قاعدة المعرفة</option>
                            <option value="1">مضاف</option>
                            <option value="0">غير مضاف</option>
                        </select>

                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-100 dark:bg-gray-900 rounded-xl p-1">
                            <button @click="viewMode = 'grid'"
                                    :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                                    class="p-2 rounded-lg transition">
                                <svg class="w-5 h-5" :class="viewMode === 'grid' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'"
                                    :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                                    class="p-2 rounded-lg transition">
                                <svg class="w-5 h-5" :class="viewMode === 'list' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Reset Filters -->
                        <button @click="resetFilters()"
                                class="p-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition"
                                title="إعادة تعيين الفلاتر">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div x-show="selectedPosts.length > 0" x-transition class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            تم تحديد <span class="font-bold text-blue-600" x-text="selectedPosts.length"></span> منشور
                        </span>
                        <button @click="bulkAddToKB()" class="px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-sm font-medium transition">
                            إضافة إلى قاعدة المعرفة
                        </button>
                        <button @click="bulkAnalyze()" class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-medium transition">
                            تحليل المحدد
                        </button>
                        <button @click="clearSelection()" class="px-4 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm transition">
                            إلغاء التحديد
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" x-transition class="flex flex-col items-center justify-center py-20">
            <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 dark:text-gray-400">جاري تحميل المنشورات...</p>
        </div>

        <!-- Grid View -->
        <div x-show="!loading && posts.length > 0 && viewMode === 'grid'" x-transition
             class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
            <template x-for="post in posts" :key="post.id">
                <div class="post-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden group">
                    <!-- Media Section -->
                    <div class="relative aspect-square bg-gray-100 dark:bg-gray-900 cursor-pointer" @click="viewPost(post)">
                        <!-- Main Media -->
                        <template x-if="getPostMedia(post).length > 0">
                            <div class="w-full h-full">
                                <!-- Single Image/Video -->
                                <template x-if="getPostMedia(post).length === 1">
                                    <div class="w-full h-full relative">
                                        <img x-show="getMediaType(getPostMedia(post)[0]) === 'image'"
                                             :src="getPostMedia(post)[0]"
                                             class="w-full h-full object-cover"
                                             alt="">
                                        <div x-show="getMediaType(getPostMedia(post)[0]) === 'video'"
                                             class="w-full h-full bg-gray-900 flex items-center justify-center">
                                            <img :src="post.thumbnail_url || getPostMedia(post)[0]"
                                                 class="w-full h-full object-cover opacity-80"
                                                 alt="">
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <div class="w-16 h-16 bg-black/60 rounded-full flex items-center justify-center backdrop-blur">
                                                    <svg class="w-8 h-8 text-white mr-[-4px]" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Multiple Media (Carousel/Album) -->
                                <template x-if="getPostMedia(post).length > 1">
                                    <div class="w-full h-full relative">
                                        <img :src="getPostMedia(post)[0]" class="w-full h-full object-cover" alt="">
                                        <div class="absolute top-3 left-3 bg-black/60 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-white text-xs font-medium" x-text="getPostMedia(post).length"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- No Media Placeholder -->
                        <template x-if="getPostMedia(post).length === 0">
                            <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-3" x-text="post.content || 'لا يوجد محتوى'"></p>
                            </div>
                        </template>

                        <!-- Platform Badge -->
                        <div class="absolute top-3 right-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                                 :class="getPlatformBgClass(post.platform)">
                                <span x-html="getPlatformIcon(post.platform)"></span>
                            </div>
                        </div>

                        <!-- Selection Checkbox -->
                        <div class="absolute top-3 left-3 opacity-0 group-hover:opacity-100 transition"
                             :class="selectedPosts.includes(post.id) ? 'opacity-100' : ''">
                            <label class="cursor-pointer">
                                <input type="checkbox" :value="post.id" x-model="selectedPosts"
                                       class="sr-only">
                                <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition"
                                     :class="selectedPosts.includes(post.id) ? 'bg-blue-600 border-blue-600' : 'bg-white/80 border-white'">
                                    <svg x-show="selectedPosts.includes(post.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </label>
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition">
                            <div class="absolute bottom-4 left-4 right-4 flex justify-center gap-2">
                                <button @click.stop="viewPost(post)" class="p-2 bg-white/90 hover:bg-white rounded-full shadow transition">
                                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button x-show="!post.is_analyzed" @click.stop="analyzePost(post.id)" class="p-2 bg-green-500 hover:bg-green-600 rounded-full shadow transition">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </button>
                                <button x-show="!post.is_in_knowledge_base" @click.stop="addToKB([post.id])" class="p-2 bg-purple-500 hover:bg-purple-600 rounded-full shadow transition">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-4">
                        <!-- Caption -->
                        <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-2 mb-3" x-text="post.content || 'بدون تعليق'"></p>

                        <!-- Metrics -->
                        <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-3">
                            <span class="flex items-center gap-1" x-show="getMetric(post, 'likes') > 0">
                                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <span x-text="formatNumber(getMetric(post, 'likes'))"></span>
                            </span>
                            <span class="flex items-center gap-1" x-show="getMetric(post, 'comments') > 0">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <span x-text="formatNumber(getMetric(post, 'comments'))"></span>
                            </span>
                            <span class="flex items-center gap-1" x-show="getMetric(post, 'shares') > 0">
                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                <span x-text="formatNumber(getMetric(post, 'shares'))"></span>
                            </span>
                        </div>

                        <!-- Success Score -->
                        <div x-show="post.is_analyzed" class="mb-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :class="getScoreColorClass(post.success_score)"
                                         :style="`width: ${(post.success_score || 0) * 100}%`"></div>
                                </div>
                                <span class="text-xs font-bold" :class="getScoreTextClass(post.success_score)"
                                      x-text="((post.success_score || 0) * 100).toFixed(0) + '%'"></span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400" x-text="formatDate(post.published_at)"></span>
                            <div class="flex items-center gap-1">
                                <span x-show="post.is_in_knowledge_base"
                                      class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs rounded-full">
                                    KB
                                </span>
                                <span x-show="post.is_analyzed"
                                      class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full">
                                    محلل
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- List View -->
        <div x-show="!loading && posts.length > 0 && viewMode === 'list'" x-transition
             class="space-y-4">
            <template x-for="post in posts" :key="post.id">
                <div class="post-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="flex flex-col md:flex-row">
                        <!-- Media Section -->
                        <div class="md:w-64 lg:w-80 flex-shrink-0">
                            <div class="relative aspect-video md:aspect-square bg-gray-100 dark:bg-gray-900 cursor-pointer" @click="viewPost(post)">
                                <template x-if="getPostMedia(post).length > 0">
                                    <img :src="getPostMedia(post)[0]" class="w-full h-full object-cover" alt="">
                                </template>
                                <template x-if="getPostMedia(post).length === 0">
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </template>

                                <!-- Media count badge -->
                                <div x-show="getPostMedia(post).length > 1"
                                     class="absolute top-3 left-3 bg-black/60 backdrop-blur px-2 py-1 rounded-lg flex items-center gap-1">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-white text-xs font-medium" x-text="getPostMedia(post).length"></span>
                                </div>

                                <!-- Platform Badge -->
                                <div class="absolute top-3 right-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center shadow-lg"
                                         :class="getPlatformBgClass(post.platform)">
                                        <span x-html="getPlatformIcon(post.platform)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Section -->
                        <div class="flex-1 p-4 md:p-6">
                            <div class="flex items-start gap-4">
                                <!-- Checkbox -->
                                <div class="flex-shrink-0 pt-1">
                                    <label class="cursor-pointer">
                                        <input type="checkbox" :value="post.id" x-model="selectedPosts" class="sr-only">
                                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition"
                                             :class="selectedPosts.includes(post.id) ? 'bg-blue-600 border-blue-600' : 'border-gray-300 dark:border-gray-600'">
                                            <svg x-show="selectedPosts.includes(post.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                    </label>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <!-- Header -->
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(post.published_at)"></span>
                                        <span x-show="post.is_in_knowledge_base"
                                              class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs rounded-full font-medium">
                                            في قاعدة المعرفة
                                        </span>
                                        <span x-show="post.is_analyzed"
                                              class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs rounded-full font-medium">
                                            تم التحليل
                                        </span>
                                    </div>

                                    <!-- Caption -->
                                    <p class="text-gray-700 dark:text-gray-300 text-sm md:text-base line-clamp-3 mb-4" x-text="post.content || 'بدون تعليق'"></p>

                                    <!-- Metrics Row -->
                                    <div class="flex flex-wrap items-center gap-4 mb-4">
                                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1" x-show="getMetric(post, 'likes') > 0">
                                                <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                                </svg>
                                                <span x-text="formatNumber(getMetric(post, 'likes'))"></span>
                                            </span>
                                            <span class="flex items-center gap-1" x-show="getMetric(post, 'comments') > 0">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                                </svg>
                                                <span x-text="formatNumber(getMetric(post, 'comments'))"></span>
                                            </span>
                                            <span class="flex items-center gap-1" x-show="getMetric(post, 'shares') > 0">
                                                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                                </svg>
                                                <span x-text="formatNumber(getMetric(post, 'shares'))"></span>
                                            </span>
                                        </div>

                                        <!-- Success Score -->
                                        <div x-show="post.is_analyzed" class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">درجة النجاح:</span>
                                            <div class="w-24 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full"
                                                     :class="getScoreColorClass(post.success_score)"
                                                     :style="`width: ${(post.success_score || 0) * 100}%`"></div>
                                            </div>
                                            <span class="text-xs font-bold" :class="getScoreTextClass(post.success_score)"
                                                  x-text="((post.success_score || 0) * 100).toFixed(0) + '%'"></span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button @click="viewPost(post)"
                                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm transition">
                                            عرض التفاصيل
                                        </button>
                                        <button x-show="!post.is_analyzed" @click="analyzePost(post.id)"
                                                class="px-3 py-1.5 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm transition">
                                            تحليل
                                        </button>
                                        <button x-show="!post.is_in_knowledge_base" @click="addToKB([post.id])"
                                                class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-sm transition">
                                            إضافة للمعرفة
                                        </button>
                                        <button x-show="post.is_in_knowledge_base" @click="removeFromKB([post.id])"
                                                class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm transition">
                                            إزالة من المعرفة
                                        </button>
                                        <a x-show="post.permalink" :href="post.permalink" target="_blank"
                                           class="px-3 py-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm transition">
                                            عرض الأصلي ←
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">لا توجد منشورات بعد</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-8">
                    ابدأ باستيراد محتواك التاريخي من منصات التواصل الاجتماعي لبناء قاعدة معرفة ذكية
                </p>
                <button @click="showImportModal = true"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-6 py-3 rounded-xl font-medium shadow-lg shadow-blue-500/25 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    استيراد المنشورات الآن
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && posts.length > 0" class="mt-8 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                عرض <span class="font-medium text-gray-900 dark:text-white" x-text="posts.length"></span> منشور
            </p>
            <div class="flex gap-2">
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50" disabled>
                    السابق
                </button>
                <button class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50" disabled>
                    التالي
                </button>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="showImportModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showImportModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-3xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">استيراد المنشورات</h3>
                                <p class="text-blue-100 text-sm">من حساباتك المتصلة</p>
                            </div>
                        </div>
                        <button @click="showImportModal = false" class="p-2 hover:bg-white/20 rounded-xl transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Platform Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            اختر المنصة <span class="text-red-500">*</span>
                        </label>
                        <select x-model="importData.integration_id"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                            <option value="">-- اختر منصة متصلة --</option>
                            <template x-for="integration in integrations" :key="integration.integration_id">
                                <option :value="integration.integration_id"
                                        x-text="getPlatformName(integration.platform_type) + ' - ' + (integration.account_name || integration.username || 'غير معروف')">
                                </option>
                            </template>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">من تاريخ</label>
                            <input type="date" x-model="importData.start_date"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">إلى تاريخ</label>
                            <input type="date" x-model="importData.end_date"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                        </div>
                    </div>

                    <!-- Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            عدد المنشورات
                        </label>
                        <input type="number" x-model="importData.limit" min="1" max="500" placeholder="100"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">الحد الأقصى: 500 منشور</p>
                    </div>

                    <!-- Auto Analyze Toggle -->
                    <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl cursor-pointer group">
                        <div>
                            <span class="font-medium text-gray-900 dark:text-white">تحليل تلقائي</span>
                            <p class="text-sm text-gray-500 dark:text-gray-400">تحليل المنشورات بالذكاء الاصطناعي</p>
                        </div>
                        <div class="relative">
                            <input type="checkbox" x-model="importData.auto_analyze" class="sr-only">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full transition"
                                 :class="importData.auto_analyze ? 'bg-blue-600' : ''"></div>
                            <div class="absolute top-0.5 right-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                                 :class="importData.auto_analyze ? '-translate-x-5' : ''"></div>
                        </div>
                    </label>
                </div>

                <!-- Footer -->
                <div class="p-6 pt-0">
                    <button @click="startImport()"
                            :disabled="!importData.integration_id || importing"
                            class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-400 text-white font-bold rounded-xl shadow-lg shadow-blue-500/25 disabled:shadow-none transition flex items-center justify-center gap-2">
                        <template x-if="!importing">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                بدء الاستيراد
                            </span>
                        </template>
                        <template x-if="importing">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                جاري الاستيراد...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Post Detail Modal -->
    <div x-show="showDetailModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="flex items-start justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="showDetailModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-4xl w-full my-8 shadow-2xl overflow-hidden"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">

                <template x-if="selectedPost">
                    <div class="flex flex-col lg:flex-row">
                        <!-- Media Section -->
                        <div class="lg:w-1/2 bg-black">
                            <div class="relative aspect-square">
                                <template x-if="getPostMedia(selectedPost).length > 0">
                                    <div class="w-full h-full">
                                        <img :src="getPostMedia(selectedPost)[currentMediaIndex]"
                                             class="w-full h-full object-contain"
                                             alt="">

                                        <!-- Navigation arrows -->
                                        <template x-if="getPostMedia(selectedPost).length > 1">
                                            <div>
                                                <button @click="currentMediaIndex = (currentMediaIndex - 1 + getPostMedia(selectedPost).length) % getPostMedia(selectedPost).length"
                                                        class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                    </svg>
                                                </button>
                                                <button @click="currentMediaIndex = (currentMediaIndex + 1) % getPostMedia(selectedPost).length"
                                                        class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 hover:bg-white rounded-full flex items-center justify-center shadow-lg transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>

                                                <!-- Dots -->
                                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5">
                                                    <template x-for="(_, idx) in getPostMedia(selectedPost)" :key="idx">
                                                        <button @click="currentMediaIndex = idx"
                                                                class="w-2 h-2 rounded-full transition"
                                                                :class="idx === currentMediaIndex ? 'bg-white' : 'bg-white/50'">
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="getPostMedia(selectedPost).length === 0">
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-900">
                                        <svg class="w-20 h-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="lg:w-1/2 flex flex-col max-h-[80vh]">
                            <!-- Header -->
                            <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                             :class="getPlatformBgClass(selectedPost.platform)">
                                            <span x-html="getPlatformIcon(selectedPost.platform)"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white capitalize" x-text="selectedPost.platform"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(selectedPost.published_at)"></p>
                                        </div>
                                    </div>
                                    <button @click="showDetailModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                                <!-- Caption -->
                                <p class="text-gray-700 dark:text-gray-300 mb-6 whitespace-pre-wrap" x-text="selectedPost.content || 'بدون تعليق'"></p>

                                <!-- Metrics -->
                                <div class="grid grid-cols-3 gap-4 mb-6">
                                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(getMetric(selectedPost, 'likes'))"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">إعجاب</p>
                                    </div>
                                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(getMetric(selectedPost, 'comments'))"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">تعليق</p>
                                    </div>
                                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(getMetric(selectedPost, 'shares'))"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">مشاركة</p>
                                    </div>
                                </div>

                                <!-- Success Score -->
                                <div x-show="selectedPost.is_analyzed" class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">درجة النجاح</span>
                                        <span class="text-lg font-bold" :class="getScoreTextClass(selectedPost.success_score)"
                                              x-text="((selectedPost.success_score || 0) * 100).toFixed(0) + '%'"></span>
                                    </div>
                                    <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                             :class="getScoreColorClass(selectedPost.success_score)"
                                             :style="`width: ${(selectedPost.success_score || 0) * 100}%`"></div>
                                    </div>
                                    <p x-show="selectedPost.success_hypothesis" class="mt-3 text-sm text-gray-600 dark:text-gray-400"
                                       x-text="selectedPost.success_hypothesis"></p>
                                </div>

                                <!-- Status Badges -->
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <span x-show="selectedPost.is_in_knowledge_base"
                                          class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm font-medium">
                                        في قاعدة المعرفة
                                    </span>
                                    <span x-show="selectedPost.is_analyzed"
                                          class="px-3 py-1.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full text-sm font-medium">
                                        تم التحليل
                                    </span>
                                    <span x-show="!selectedPost.is_analyzed"
                                          class="px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full text-sm font-medium">
                                        بانتظار التحليل
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="p-6 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex flex-wrap gap-2">
                                    <button x-show="!selectedPost.is_analyzed" @click="analyzePost(selectedPost.id)"
                                            class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition">
                                        تحليل المنشور
                                    </button>
                                    <button x-show="!selectedPost.is_in_knowledge_base" @click="addToKB([selectedPost.id])"
                                            class="flex-1 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition">
                                        إضافة للمعرفة
                                    </button>
                                    <button x-show="selectedPost.is_in_knowledge_base" @click="removeFromKB([selectedPost.id])"
                                            class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium transition">
                                        إزالة من المعرفة
                                    </button>
                                    <a x-show="selectedPost.permalink" :href="selectedPost.permalink" target="_blank"
                                       class="px-6 py-3 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl font-medium transition text-center dark:text-white">
                                        عرض الأصلي
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Knowledge Base Modal -->
    <div x-show="showKBModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showKBModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl p-6">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">قاعدة المعرفة</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        قاعدة المعرفة تحتوي على <span class="font-bold text-purple-600" x-text="stats.inKB"></span> منشور
                    </p>
                    <button @click="showKBModal = false"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition dark:text-white">
                        إغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function historicalContentManager(orgId, csrfToken) {
    return {
        orgId: orgId,
        csrfToken: csrfToken,
        posts: [],
        profileGroups: [],
        integrations: [],
        selectedPosts: [],
        campaigns: [],
        loading: false,
        importing: false,
        viewMode: 'grid',
        showImportModal: false,
        showKBModal: false,
        showDetailModal: false,
        selectedPost: null,
        currentMediaIndex: 0,
        searchQuery: '',
        filters: {
            profile_group_id: '',
            platform: '',
            is_analyzed: '',
            is_in_kb: '',
            min_success_score: 0
        },
        stats: {
            totalImported: 0,
            totalAnalyzed: 0,
            inKB: 0,
            highPerformers: 0
        },
        importData: {
            integration_id: '',
            limit: 100,
            start_date: '',
            end_date: '',
            auto_analyze: true
        },

        init() {
            if (!this.orgId) return;

            this.loadIntegrations();
            this.loadPosts();

            // Set default date range
            const today = new Date();
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(today.getMonth() - 6);
            this.importData.start_date = sixMonthsAgo.toISOString().split('T')[0];
            this.importData.end_date = today.toISOString().split('T')[0];
        },

        async loadPosts() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value !== '' && value !== null && !(key === 'min_success_score' && parseFloat(value) === 0)) {
                        params.append(key, value);
                    }
                });

                const response = await fetch(`/orgs/${this.orgId}/social/history/api/posts?${params}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    this.posts = data.data?.data || data.data || [];
                    this.updateStats();
                }
            } catch (error) {
                console.error('Failed to load posts:', error);
            } finally {
                this.loading = false;
            }
        },

        updateStats() {
            this.stats.totalImported = this.posts.length;
            this.stats.totalAnalyzed = this.posts.filter(p => p.is_analyzed).length;
            this.stats.inKB = this.posts.filter(p => p.is_in_knowledge_base).length;
            this.stats.highPerformers = this.posts.filter(p => p.success_label === 'high_performer').length;
        },

        async loadIntegrations() {
            try {
                const response = await fetch(`/orgs/${this.orgId}/settings/platform-connections/api/list`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (data.success) {
                    this.integrations = (data.data || []).filter(i =>
                        ['instagram', 'facebook', 'threads', 'twitter', 'linkedin', 'tiktok'].includes(i.platform_type)
                    );
                }
            } catch (error) {
                console.error('Failed to load integrations:', error);
            }
        },

        async startImport() {
            if (!this.importData.integration_id) return;
            this.importing = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/history/api/import`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        integration_id: this.importData.integration_id,
                        limit: this.importData.limit || 100,
                        start_date: this.importData.start_date,
                        end_date: this.importData.end_date,
                        auto_analyze: this.importData.auto_analyze,
                        async: true
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showImportModal = false;
                    this.importData.integration_id = '';
                    setTimeout(() => this.loadPosts(), 3000);
                }
            } catch (error) {
                console.error('Failed to import:', error);
            } finally {
                this.importing = false;
            }
        },

        async analyzePost(postId) {
            try {
                await fetch(`/orgs/${this.orgId}/social/history/api/posts/${postId}/analyze`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    }
                });
                setTimeout(() => this.loadPosts(), 2000);
            } catch (error) {
                console.error('Failed to analyze:', error);
            }
        },

        async addToKB(postIds) {
            try {
                await fetch(`/orgs/${this.orgId}/social/history/api/kb/add`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });
                this.loadPosts();
            } catch (error) {
                console.error('Failed to add to KB:', error);
            }
        },

        async removeFromKB(postIds) {
            try {
                await fetch(`/orgs/${this.orgId}/social/history/api/kb/remove`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({ post_ids: postIds })
                });
                this.loadPosts();
            } catch (error) {
                console.error('Failed to remove from KB:', error);
            }
        },

        bulkAddToKB() {
            this.addToKB(this.selectedPosts);
            this.selectedPosts = [];
        },

        bulkAnalyze() {
            this.selectedPosts.forEach(id => this.analyzePost(id));
            this.selectedPosts = [];
        },

        clearSelection() {
            this.selectedPosts = [];
        },

        resetFilters() {
            this.filters = { profile_group_id: '', platform: '', is_analyzed: '', is_in_kb: '', min_success_score: 0 };
            this.loadPosts();
        },

        viewPost(post) {
            this.selectedPost = post;
            this.currentMediaIndex = 0;
            this.showDetailModal = true;
        },

        getPostMedia(post) {
            let media = [];

            // Check metadata for full_picture
            if (post.metadata?.platform_data?.full_picture) {
                media.push(post.metadata.platform_data.full_picture);
            }

            // Check direct media_url
            if (post.media_url) {
                media.push(post.media_url);
            }

            // Check media array
            if (post.media && Array.isArray(post.media)) {
                post.media.forEach(m => {
                    if (typeof m === 'string') media.push(m);
                    else if (m.url) media.push(m.url);
                });
            }

            // Check media_assets relation
            if (post.media_assets && Array.isArray(post.media_assets)) {
                post.media_assets.forEach(m => {
                    if (m.original_url) media.push(m.original_url);
                });
            }

            // Check children (carousel)
            if (post.metadata?.platform_data?.children?.data) {
                post.metadata.platform_data.children.data.forEach(child => {
                    if (child.media_url) media.push(child.media_url);
                });
            }

            return [...new Set(media)]; // Remove duplicates
        },

        getMediaType(url) {
            if (!url) return 'image';
            if (url.match(/\.(mp4|mov|avi|webm)$/i)) return 'video';
            return 'image';
        },

        getMetric(post, type) {
            // Check engagement directly
            if (post.engagement_cache && type === 'likes') return post.engagement_cache;

            // Check metadata
            const platformData = post.metadata?.platform_data;
            if (platformData) {
                if (type === 'likes') return platformData.likes?.summary?.total_count || platformData.like_count || 0;
                if (type === 'comments') return platformData.comments?.summary?.total_count || platformData.comments_count || 0;
                if (type === 'shares') return platformData.shares?.count || 0;
            }

            return 0;
        },

        formatNumber(num) {
            if (!num) return '0';
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleDateString('ar-SA', { year: 'numeric', month: 'short', day: 'numeric' });
        },

        getPlatformName(platform) {
            const names = { instagram: 'إنستغرام', facebook: 'فيسبوك', threads: 'ثريدز', twitter: 'تويتر', linkedin: 'لينكد إن', tiktok: 'تيك توك' };
            return names[platform] || platform;
        },

        getPlatformBgClass(platform) {
            const classes = {
                facebook: 'bg-[#1877F2]',
                instagram: 'bg-gradient-to-br from-[#833AB4] via-[#FD1D1D] to-[#F77737]',
                twitter: 'bg-[#1DA1F2]',
                linkedin: 'bg-[#0A66C2]',
                tiktok: 'bg-black',
                threads: 'bg-black'
            };
            return classes[platform] || 'bg-gray-500';
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                instagram: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
                twitter: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>',
                linkedin: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                tiktok: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
                threads: '<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.472 12.01v-.017c.03-3.579.879-6.43 2.525-8.482C5.845 1.205 8.6.024 12.18 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.589 12c.027 3.086.718 5.496 2.057 7.164 1.43 1.783 3.631 2.698 6.54 2.717 2.623-.02 4.358-.631 5.8-2.045 1.647-1.613 1.618-3.593 1.09-4.798-.31-.71-.873-1.3-1.634-1.75-.192 1.352-.622 2.446-1.284 3.272-.886 1.102-2.14 1.704-3.73 1.79-1.202.065-2.361-.218-3.259-.801-1.063-.689-1.685-1.74-1.752-2.96-.065-1.182.408-2.256 1.33-3.022.88-.73 2.082-1.123 3.479-1.14.967-.01 1.904.132 2.794.425.02-.455.038-.882.022-1.325-.057-1.254-.407-2.21-1.04-2.843-.658-.657-1.627-.98-2.88-.96-1.134.017-2.072.343-2.793.97-.654.566-1.075 1.322-1.253 2.25l-2.019-.457c.268-1.344.893-2.47 1.86-3.35 1.062-.968 2.442-1.478 4.1-1.518h.095c1.867.037 3.368.572 4.462 1.588 1.058.98 1.633 2.355 1.708 4.085.012.384-.006.781-.054 1.186 1.156.553 2.095 1.334 2.763 2.295.872 1.257 1.058 2.79.523 4.32-.55 1.574-1.77 2.923-3.433 3.797-1.527.8-3.346 1.21-5.41 1.218l-.036-.001z"/></svg>'
            };
            return icons[platform] || '';
        },

        getScoreColorClass(score) {
            if (!score) return 'bg-gray-300';
            if (score >= 0.7) return 'bg-gradient-to-r from-green-400 to-green-600';
            if (score >= 0.4) return 'bg-gradient-to-r from-yellow-400 to-yellow-600';
            return 'bg-gradient-to-r from-red-400 to-red-600';
        },

        getScoreTextClass(score) {
            if (!score) return 'text-gray-500';
            if (score >= 0.7) return 'text-green-600';
            if (score >= 0.4) return 'text-yellow-600';
            return 'text-red-600';
        }
    };
}
</script>
@endpush

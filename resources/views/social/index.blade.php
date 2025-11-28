@extends('layouts.admin')

@section('page-title', __('social.social_management'))
@section('page-subtitle', __('social.schedule_publish_description'))

@section('content')
<div x-data="socialManager()" x-init="init()">
    <!-- Quick Stats Dashboard - Enhanced with animations and dark mode -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-6">
        <!-- Scheduled Posts Card -->
        <div class="group bg-gradient-to-br from-yellow-400 to-orange-500 dark:from-yellow-500 dark:to-orange-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
             @click="statusFilter = 'scheduled'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-xs sm:text-sm font-medium">{{ __('social.scheduled_status') }}</p>
                    <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="scheduledCount">0</p>
                </div>
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                    <i class="fas fa-clock text-xl sm:text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-yellow-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
                <i class="fas fa-calendar-alt ms-1"></i>
                <span>{{ __('social.waiting_publish') }}</span>
            </div>
        </div>

        <!-- Published Posts Card -->
        <div class="group bg-gradient-to-br from-green-400 to-emerald-500 dark:from-green-500 dark:to-emerald-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-emerald-500/20 hover:shadow-xl hover:shadow-emerald-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
             @click="statusFilter = 'published'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-xs sm:text-sm font-medium">{{ __('social.published_status') }}</p>
                    <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="publishedCount">0</p>
                </div>
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                    <i class="fas fa-check-circle text-xl sm:text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-green-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
                <i class="fas fa-chart-line ms-1"></i>
                <span>{{ __('social.published_successfully') }}</span>
            </div>
        </div>

        <!-- Draft Posts Card -->
        <div class="group bg-gradient-to-br from-slate-400 to-slate-500 dark:from-slate-500 dark:to-slate-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-slate-500/20 hover:shadow-xl hover:shadow-slate-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
             @click="statusFilter = 'draft'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-100 text-xs sm:text-sm font-medium">{{ __('social.draft_status') }}</p>
                    <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="draftCount">0</p>
                </div>
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                    <i class="fas fa-file-alt text-xl sm:text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-slate-100 text-xs opacity-80 group-hover:opacity-100 transition-opacity">
                <i class="fas fa-edit ms-1"></i>
                <span>{{ __('social.ready_edit') }}</span>
            </div>
        </div>

        <!-- Failed Posts Card -->
        <div class="group bg-gradient-to-br from-red-400 to-rose-500 dark:from-red-500 dark:to-rose-600 rounded-2xl p-4 sm:p-5 text-white shadow-lg shadow-rose-500/20 hover:shadow-xl hover:shadow-rose-500/30 hover:-translate-y-1 transition-all duration-300 cursor-pointer"
             @click="statusFilter = 'failed'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-xs sm:text-sm font-medium">{{ __('social.failed_status') }}</p>
                    <p class="text-2xl sm:text-3xl font-bold mt-1 tabular-nums" x-text="failedCount">0</p>
                </div>
                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 group-hover:bg-white/30 transition-all duration-300">
                    <i class="fas fa-exclamation-triangle text-xl sm:text-2xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-red-100 text-xs cursor-pointer hover:text-white transition-colors"
                 x-show="failedCount > 0" @click.stop="deleteAllFailed()">
                <i class="fas fa-trash ms-1"></i>
                <span>{{ __('social.delete_all') }}</span>
            </div>
            <div class="mt-3 flex items-center text-red-100 text-xs opacity-80" x-show="failedCount === 0">
                <i class="fas fa-smile ms-1"></i>
                <span>{{ __('social.no_errors') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Controls Panel - Enhanced with dark mode -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 mb-6">
        <!-- Top Row: Search, View Toggle, Actions -->
        <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4 mb-4">
            <!-- Search Box - Enhanced -->
            <div class="relative flex-1 max-w-md order-1 w-full sm:w-auto">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="{{ __('social.search_posts') }}"
                       class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent focus:bg-white dark:focus:bg-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-colors">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <button x-show="searchQuery" @click="searchQuery = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- View Toggle - Enhanced with dark mode -->
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-xl order-3 sm:order-2">
                <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="p-2.5 rounded-lg transition-all duration-200" title="{{ __('social.grid_view') }}">
                    <i class="fas fa-th-large"></i>
                </button>
                <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="p-2.5 rounded-lg transition-all duration-200" title="{{ __('social.list_view') }}">
                    <i class="fas fa-list"></i>
                </button>
                <button @click="viewMode = 'calendar'"
                        :class="viewMode === 'calendar' ? 'bg-white dark:bg-gray-600 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="p-2.5 rounded-lg transition-all duration-200" title="{{ __('social.calendar_view') }}">
                    <i class="fas fa-calendar-alt"></i>
                </button>
            </div>

            <!-- Sort Dropdown - Enhanced -->
            <select x-model="sortBy" class="border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 text-gray-700 dark:text-gray-200 order-4 sm:order-3">
                <option value="newest">{{ __('social.newest_first') }}</option>
                <option value="oldest">{{ __('social.oldest_first') }}</option>
                <option value="scheduled">{{ __('social.by_schedule') }}</option>
                <option value="platform">{{ __('social.by_platform') }}</option>
            </select>

            <!-- Action Buttons - Enhanced -->
            <div class="flex gap-2 sm:gap-3 order-2 sm:order-4">
                <button @click="showQueueSettings = true"
                        class="bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 px-3 sm:px-4 py-2.5 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-gray-300 dark:hover:border-gray-500 transition-all flex items-center gap-2">
                    <i class="fas fa-cog"></i>
                    <span class="hidden sm:inline">{{ __('social.queue_settings') }}</span>
                </button>
                <button @click="$dispatch('open-publish-modal')"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-6 py-2.5 rounded-xl font-medium shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">{{ __('social.new_post') }}</span>
                </button>
            </div>
        </div>

        <!-- Platform Filters - Enhanced with dark mode and animations -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterPlatform = 'all'"
                    :class="filterPlatform === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-globe ms-1"></i>
                الكل
            </button>
            <template x-for="platform in uniquePlatforms" :key="platform">
                <button @click="filterPlatform = platform"
                        :class="filterPlatform === platform ? getPlatformFilterClass(platform, true) + ' shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                    <i :class="getPlatformIcon(platform)" class="ms-1"></i>
                    <span x-text="getPlatformName(platform)"></span>
                </button>
            </template>
        </div>

        <!-- Post Type Filter - Enhanced with dark mode -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button @click="filterPostType = 'all'"
                    :class="filterPostType === 'all' ? 'bg-gray-800 dark:bg-gray-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-th-large ms-1"></i>
                الكل
            </button>
            <button @click="filterPostType = 'feed'"
                    :class="filterPostType === 'feed' ? 'bg-green-600 text-white shadow-md shadow-green-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-newspaper ms-1"></i>
                {{ __('social.feed_post') }}
            </button>
            <button @click="filterPostType = 'reel'"
                    :class="filterPostType === 'reel' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-video ms-1"></i>
                {{ __("social.reel") }}
            </button>
            <button @click="filterPostType = 'story'"
                    :class="filterPostType === 'story' ? 'bg-pink-600 text-white shadow-md shadow-pink-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-circle ms-1"></i>
                {{ __("social.story") }}
            </button>
            <button @click="filterPostType = 'carousel'"
                    :class="filterPostType === 'carousel' ? 'bg-orange-600 text-white shadow-md shadow-orange-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-images ms-1"></i>
                {{ __("social.carousel") }}
            </button>
            <button @click="filterPostType = 'thread'"
                    :class="filterPostType === 'thread' ? 'bg-sky-600 text-white shadow-md shadow-sky-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                    class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
                <i class="fas fa-stream ms-1"></i>
                ثريد
            </button>
        </div>

        <!-- Status Tabs with Bulk Actions - Enhanced -->
        <div class="flex gap-2 border-t border-gray-200 dark:border-gray-700 pt-4 items-center justify-between flex-wrap">
            <div class="flex gap-1.5 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                <button @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
                    الكل (<span x-text="posts.length" class="tabular-nums"></span>)
                </button>
                <button @click="statusFilter = 'scheduled'"
                        :class="statusFilter === 'scheduled' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-300 dark:border-yellow-700' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
                    <i class="fas fa-clock ms-1"></i>
                    {{ __("social.scheduled_status") }} (<span x-text="scheduledCount" class="tabular-nums"></span>)
                </button>
                <button @click="statusFilter = 'published'"
                        :class="statusFilter === 'published' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-300 dark:border-green-700' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
                    <i class="fas fa-check-circle ms-1"></i>
                    {{ __("social.published_status") }} (<span x-text="publishedCount" class="tabular-nums"></span>)
                </button>
                <button @click="statusFilter = 'draft'"
                        :class="statusFilter === 'draft' ? 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
                    <i class="fas fa-file ms-1"></i>
                    {{ __("social.draft_status") }} (<span x-text="draftCount" class="tabular-nums"></span>)
                </button>
                <button @click="statusFilter = 'failed'"
                        :class="statusFilter === 'failed' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-300 dark:border-red-700' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
                    <i class="fas fa-exclamation-triangle ms-1"></i>
                    {{ __("social.failed_status") }} (<span x-text="failedCount" class="tabular-nums"></span>)
                </button>
            </div>

            <!-- Bulk Actions - Enhanced -->
            <div class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 rounded-xl" x-show="selectedPosts.length > 0" x-transition>
                <span class="text-sm text-indigo-700 dark:text-indigo-300 font-medium">
                    <span x-text="selectedPosts.length" class="tabular-nums"></span> {{ __("social.selected_count") }}
                </span>
                <button @click="bulkDelete()" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium flex items-center gap-1 transition-colors">
                    <i class="fas fa-trash"></i> {{ __("common.delete") }}
                </button>
                <button @click="selectedPosts = []" class="text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-sm transition-colors">{{ __("common.cancel") }}
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar View - Enhanced with dark mode -->
    <div x-show="viewMode === 'calendar'" x-cloak class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <button @click="changeMonth(-1)" class="p-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-gray-600 dark:text-gray-400 transition-colors">
                <i class="fas fa-chevron-right"></i>
            </button>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white" x-text="currentMonthYear"></h3>
            <button @click="changeMonth(1)" class="p-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-gray-600 dark:text-gray-400 transition-colors">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <!-- Calendar Grid - Enhanced -->
        <div class="grid grid-cols-7 gap-1 sm:gap-2">
            <!-- Day Headers -->
            <template x-for="day in ["{{ __("social.days.sunday") }}", "{{ __("social.days.monday") }}", "{{ __("social.days.tuesday") }}", "{{ __("social.days.wednesday") }}", "{{ __("social.days.thursday") }}", "{{ __("social.days.friday") }}", "{{ __("social.days.saturday") }}"]">
                <div class="text-center py-2 sm:py-3 text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase" x-text="day"></div>
            </template>

            <!-- Calendar Days - Enhanced -->
            <template x-for="day in calendarDays" :key="day.date">
                <div class="min-h-[80px] sm:min-h-[100px] border border-gray-100 dark:border-gray-700 rounded-xl p-1.5 sm:p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                     :class="{
                         'bg-gray-50 dark:bg-gray-800/50 opacity-60': !day.isCurrentMonth,
                         'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800': day.isToday
                     }">
                    <div class="text-xs sm:text-sm font-semibold mb-1.5"
                         :class="day.isToday ? 'text-indigo-600 dark:text-indigo-400' : (day.isCurrentMonth ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400 dark:text-gray-600')"
                         x-text="day.dayNumber"></div>
                    <div class="space-y-1">
                        <template x-for="post in day.posts.slice(0, 2)" :key="post.post_id">
                            <div class="text-[10px] sm:text-xs p-1 sm:p-1.5 rounded-lg truncate cursor-pointer hover:opacity-90 transition-opacity font-medium"
                                 :class="{
                                     'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300': post.platform === 'facebook',
                                     'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300': post.platform === 'instagram',
                                     'bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300': post.platform === 'twitter',
                                     'bg-blue-200 dark:bg-blue-800/40 text-blue-800 dark:text-blue-200': post.platform === 'linkedin'
                                 }"
                                 @click="editPost(post)"
                                 x-text="post.post_text?.substring(0, 15) + '...'"></div>
                        </template>
                        <div x-show="day.posts.length > 2" class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 text-center font-medium">
                            +<span x-text="day.posts.length - 2"></span> {{ __("social.more") }}
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Posts Grid View - Enhanced with dark mode and animations -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <template x-for="post in sortedFilteredPosts" :key="post.post_id">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 hover:-translate-y-1 transition-all duration-300 group relative"
                 :class="{'ring-2 ring-indigo-500 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-900': selectedPosts.includes(post.post_id)}">
                <!-- Selection Checkbox - Enhanced -->
                <div class="absolute top-3 right-3 z-10">
                    <input type="checkbox"
                           :checked="selectedPosts.includes(post.post_id)"
                           @change="togglePostSelection(post.post_id)"
                           class="w-5 h-5 text-indigo-600 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:ring-offset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer bg-white dark:bg-gray-700"
                           :class="{'opacity-100': selectedPosts.includes(post.post_id)}">
                </div>

                <!-- Platform Badge & Status - Enhanced -->
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-sm"
                             :class="{
                                 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400': post.platform === 'facebook',
                                 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40 text-pink-600 dark:text-pink-400': post.platform === 'instagram',
                                 'bg-sky-100 dark:bg-sky-900/40 text-sky-500 dark:text-sky-400': post.platform === 'twitter',
                                 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300': post.platform === 'linkedin'
                             }">
                            <i :class="{
                                'fab fa-facebook-f': post.platform === 'facebook',
                                'fab fa-instagram': post.platform === 'instagram',
                                'fab fa-twitter': post.platform === 'twitter',
                                'fab fa-linkedin-in': post.platform === 'linkedin'
                            }"></i>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-200 text-sm" x-text="post.account_username || post.platform"></span>
                        </div>
                    </div>

                    <!-- Status Badge - Enhanced -->
                    <span :class="{
                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800': post.status === 'scheduled',
                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800': post.status === 'published',
                        'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600': post.status === 'draft',
                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800': post.status === 'failed'
                    }" class="px-2.5 py-1 rounded-lg text-xs font-semibold border">
                        <span x-text="getStatusLabel(post.status)"></span>
                    </span>
                </div>

                <!-- Post Content - Enhanced -->
                <div class="p-4">
                    <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-3 mb-3 leading-relaxed" x-text="post.post_text"></p>

                    <!-- Media Preview - Enhanced -->
                    <template x-if="post.media && post.media.length > 0">
                        <div class="relative mb-3 rounded-xl overflow-hidden group/media">
                            <template x-if="post.media[0].type === 'video'">
                                <div class="relative">
                                    <video :src="post.media[0].url" class="w-full h-40 object-cover"></video>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 group-hover/media:bg-black/50 transition-colors">
                                        <i class="fas fa-play-circle text-white text-5xl opacity-90 group-hover/media:opacity-100 group-hover/media:scale-110 transition-all"></i>
                                    </div>
                                </div>
                            </template>
                            <template x-if="post.media[0].type !== 'video'">
                                <img :src="post.media[0].url" class="w-full h-40 object-cover group-hover/media:scale-105 transition-transform duration-300">
                            </template>
                            <div x-show="post.media.length > 1"
                                 class="absolute bottom-2 left-2 bg-black/70 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full font-medium">
                                <i class="fas fa-images ms-1"></i>
                                <span x-text="post.media.length"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Metrics (for published posts) - Enhanced -->
                    <template x-if="post.status === 'published'">
                        <div class="flex items-center justify-between py-2.5 border-t border-b border-gray-100 dark:border-gray-700 mb-3 text-xs text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-1.5 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer" title="إعجاب">
                                <i class="far fa-heart"></i>
                                <span class="tabular-nums" x-text="formatNumber(post.likes || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1.5 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer" title="تعليق">
                                <i class="far fa-comment"></i>
                                <span class="tabular-nums" x-text="formatNumber(post.comments || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1.5 hover:text-green-500 dark:hover:text-green-400 transition-colors cursor-pointer" title="مشاركة">
                                <i class="far fa-share-square"></i>
                                <span class="tabular-nums" x-text="formatNumber(post.shares || 0)"></span>
                            </div>
                            <div class="flex items-center gap-1.5 hover:text-purple-500 dark:hover:text-purple-400 transition-colors cursor-pointer" title="وصول">
                                <i class="far fa-eye"></i>
                                <span class="tabular-nums" x-text="formatNumber(post.reach || 0)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Scheduled Time - Enhanced -->
                    <template x-if="post.scheduled_at && post.status === 'scheduled'">
                        <div class="flex items-center gap-2 text-xs text-yellow-700 dark:text-yellow-300 mb-3 bg-yellow-50 dark:bg-yellow-900/20 p-2.5 rounded-xl border border-yellow-100 dark:border-yellow-800/30">
                            <i class="fas fa-clock"></i>
                            <span x-text="formatDate(post.scheduled_at)"></span>
                        </div>
                    </template>

                    <!-- Published Time - Enhanced -->
                    <template x-if="post.published_at && post.status === 'published'">
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                            <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                            <span>نُشر: <span x-text="formatDate(post.published_at)"></span></span>
                        </div>
                    </template>

                    <!-- Error Message for Failed Posts - Enhanced -->
                    <template x-if="post.status === 'failed' && post.error_message">
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30 rounded-xl p-3 mb-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mt-0.5"></i>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-red-800 dark:text-red-300">{{ __("social.failure_reason") }}</p>
                                    <p class="text-xs text-red-700 dark:text-red-400 mt-1" x-text="post.error_message"></p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Quick Actions - Enhanced -->
                    <div class="flex items-center gap-1.5 border-t border-gray-100 dark:border-gray-700 pt-3">
                        <button @click="editPost(post)"
                                class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                title="{{ __('social.edit_post') }}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button @click="duplicatePost(post)"
                                class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                                title="{{ __('social.duplicate') }}">
                            <i class="fas fa-copy"></i>
                        </button>
                        <template x-if="post.status === 'scheduled' || post.status === 'draft'">
                            <button @click="publishNow(post.post_id)"
                                    class="flex-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                    title="{{ __('social.publish_now') }}">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </template>
                        <template x-if="post.status === 'failed'">
                            <button @click="retryPost(post.post_id)"
                                    class="flex-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                                    title="{{ __('social.retry') }}">
                                <i class="fas fa-redo"></i>
                            </button>
                        </template>
                        <template x-if="post.permalink">
                            <a :href="post.permalink" target="_blank"
                               class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center"
                               title="{{ __("social.view_post") }}">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </template>
                        <button @click="deletePost(post.post_id)"
                                class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-3 py-2 rounded-xl text-sm hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                title="{{ __("common.delete") }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Posts List View - Enhanced with dark mode -->
    <div x-show="viewMode === 'list'" x-cloak class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <input type="checkbox" @change="toggleAllPosts($event)" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600">
                    </th>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">المنصة</th>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __("social.post_content") }}</th>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">الحالة</th>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">التاريخ</th>
                    <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                <template x-for="post in sortedFilteredPosts" :key="post.post_id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-4">
                            <input type="checkbox"
                                   :checked="selectedPosts.includes(post.post_id)"
                                   @change="togglePostSelection(post.post_id)"
                                   class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600">
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                     :class="{
                                         'bg-blue-100 dark:bg-blue-900/40': post.platform === 'facebook',
                                         'bg-pink-100 dark:bg-pink-900/40': post.platform === 'instagram',
                                         'bg-sky-100 dark:bg-sky-900/40': post.platform === 'twitter',
                                         'bg-blue-100 dark:bg-blue-900/40': post.platform === 'linkedin'
                                     }">
                                    <i :class="{
                                        'fab fa-facebook text-blue-600 dark:text-blue-400': post.platform === 'facebook',
                                        'fab fa-instagram text-pink-600 dark:text-pink-400': post.platform === 'instagram',
                                        'fab fa-twitter text-sky-500 dark:text-sky-400': post.platform === 'twitter',
                                        'fab fa-linkedin text-blue-700 dark:text-blue-300': post.platform === 'linkedin'
                                    }"></i>
                                </div>
                                <span class="text-sm text-gray-700 dark:text-gray-300 font-medium" x-text="post.account_username || post.platform"></span>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-3">
                                <template x-if="post.media && post.media.length > 0">
                                    <img :src="post.media[0].url" class="w-10 h-10 object-cover rounded-lg shadow-sm">
                                </template>
                                <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 max-w-xs" x-text="post.post_text"></p>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <span :class="{
                                'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300': post.status === 'scheduled',
                                'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300': post.status === 'published',
                                'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': post.status === 'draft',
                                'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300': post.status === 'failed'
                            }" class="px-2.5 py-1 rounded-lg text-xs font-semibold" x-text="getStatusLabel(post.status)"></span>
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="formatDate(post.scheduled_at || post.published_at || post.created_at)"></span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center gap-1">
                                <button @click="editPost(post)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="{{ __(\'social.edit_post\') }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="duplicatePost(post)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="نسخ">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button @click="deletePost(post.post_id)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="{{ __("common.delete") }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Empty state for list view - Enhanced -->
        <div x-show="sortedFilteredPosts.length === 0" class="py-16 text-center">
            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-inbox text-3xl text-gray-400 dark:text-gray-500"></i>
            </div>
            <p class="text-gray-500 dark:text-gray-400 font-medium">{{ __("social.no_posts_found") }}</p>
        </div>
    </div>

    <!-- Empty State - Enhanced -->
    <template x-if="sortedFilteredPosts.length === 0 && viewMode === 'grid'">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 sm:p-16 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-500/10">
                <i class="fas fa-calendar-plus text-indigo-600 dark:text-indigo-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">{{ __("social.no_posts_found") }}</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm mx-auto">{{ __("social.create_post_description") }}</p>
            <button @click="$dispatch('open-publish-modal')"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-8 py-3.5 rounded-xl font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all">
                <i class="fas fa-plus ms-2"></i>
                {{ __("social.create_new_post") }}
            </button>
        </div>
    </template>

    <!-- Edit Post Modal -->
    <div x-show="showEditPostModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="showEditPostModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-edit text-indigo-600 ms-2"></i>
                    {{ __("social.edit_post") }}
                </h3>
                <button @click="showEditPostModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Platform Info -->
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-blue-100 text-blue-600': editingPost.platform === 'facebook',
                             'bg-gradient-to-br from-purple-100 to-pink-100 text-pink-600': editingPost.platform === 'instagram',
                             'bg-sky-100 text-sky-600': editingPost.platform === 'twitter',
                             'bg-blue-100 text-blue-700': editingPost.platform === 'linkedin'
                         }">
                        <i :class="{
                            'fab fa-facebook-f': editingPost.platform === 'facebook',
                            'fab fa-instagram': editingPost.platform === 'instagram',
                            'fab fa-twitter': editingPost.platform === 'twitter',
                            'fab fa-linkedin-in': editingPost.platform === 'linkedin'
                        }" class="text-lg"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white" x-text="editingPost.account_username || editingPost.platform"></p>
                        <p class="text-xs text-gray-500">
                            <span x-text="editingPost.platform"></span>
                            •
                            <span :class="{
                                'text-yellow-600': editingPost.status === 'scheduled',
                                'text-green-600': editingPost.status === 'published',
                                'text-gray-600': editingPost.status === 'draft',
                                'text-red-600': editingPost.status === 'failed'
                            }" x-text="getStatusLabel(editingPost.status)"></span>
                        </p>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pen ms-1"></i>
                        {{ __('social.post_content') }}
                    </label>
                    <textarea x-model="editingPost.content" rows="5"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-4 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="{{ __("social.post_content_placeholder") }}"></textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="editingPost.content.length"></span> حرف
                    </div>
                </div>

                <!-- Current Media Preview -->
                <template x-if="editingPost.media && editingPost.media.length > 0">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-image ms-1"></i>
                            الوسائط الحالية
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="(media, index) in editingPost.media" :key="index">
                                <div class="relative">
                                    <template x-if="media.type === 'video'">
                                        <div class="relative">
                                            <video :src="media.url" class="w-full h-20 object-cover rounded-lg"></video>
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-lg">
                                                <i class="fas fa-play-circle text-white text-xl"></i>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="media.type === 'image' || !media.type">
                                        <img :src="media.url" class="w-full h-20 object-cover rounded-lg">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Schedule (for draft/scheduled posts) -->
                <template x-if="editingPost.status === 'draft' || editingPost.status === 'scheduled'">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <i class="fas fa-clock ms-1"></i>
                            {{ __('social.schedule_datetime') }}
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">التاريخ</label>
                                <input type="date" x-model="editingPost.scheduledDate"
                                       :min="minDate"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">الوقت</label>
                                <input type="time" x-model="editingPost.scheduledTime"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <button @click="showEditPostModal = false"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    {{ __('common.cancel') }}
                </button>
                <div class="flex gap-3">
                    <button @click="updatePost()"
                            :disabled="isUpdating || !editingPost.content.trim()"
                            class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isUpdating">
                            <i class="fas fa-save ms-2"></i>
                            حفظ التغييرات
                        </span>
                        <span x-show="isUpdating">
                            <i class="fas fa-spinner fa-spin ms-2"></i>
                            جاري الحفظ...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Settings Modal - Enhanced UI/UX -->
    <div x-show="showQueueSettings" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
         @click.self="showQueueSettings = false"
         @keydown.escape.window="showQueueSettings = false">
        <div x-show="showQueueSettings"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col"
             @click.stop>

            <!-- Header - Enhanced with pattern and better hierarchy -->
            <div class="relative p-6 sm:p-8 bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-700 text-white overflow-hidden">
                <!-- Subtle pattern overlay -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)"/>
                    </svg>
                </div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                                <i class="fas fa-rocket text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl sm:text-2xl font-bold leading-tight">
                                    {{ __("social.auto_publish_settings") }}
                                </h3>
                                <p class="text-purple-200 text-sm mt-0.5 leading-relaxed">
                                    {{ __("social.smart_scheduling_description") }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Close Button - Enhanced -->
                    <button @click="showQueueSettings = false"
                            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white transition-all focus:outline-none focus:ring-2 focus:ring-white/50"
                            aria-label="إغلاق">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body - Enhanced with better spacing -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50 dark:bg-gray-900/50">
                <div class="max-w-3xl mx-auto space-y-5">

                    <!-- Info Banner - Collapsible with better design -->
                    <div x-data="{ showInfo: true }"
                         class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200/50 dark:border-blue-800/50 rounded-2xl overflow-hidden">
                        <button @click="showInfo = !showInfo"
                                class="w-full p-4 flex items-center gap-3 text-end hover:bg-blue-100/50 dark:hover:bg-blue-900/30 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                            <div class="w-10 h-10 bg-blue-500 dark:bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/30">
                                <i class="fas fa-lightbulb text-white"></i>
                            </div>
                            <div class="flex-1 text-end">
                                <p class="text-sm font-bold text-blue-900 dark:text-blue-100">{{ __("social.how_auto_publish_works") }}</p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5" x-show="!showInfo">{{ __("social.click_for_details") }}</p>
                            </div>
                            <i class="fas fa-chevron-down text-blue-500 dark:text-blue-400 transition-transform duration-200" :class="{ 'rotate-180': showInfo }"></i>
                        </button>
                        <div x-show="showInfo" x-collapse class="px-4 pb-4">
                            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-4 space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">1</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.enable_auto_publish_instruction") }}</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">2</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.set_publish_times_instruction") }}</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">3</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.auto_publish_description") }}.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Queue Settings - Enhanced Cards -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2 px-1">
                            <i class="fas fa-plug"></i>
                            الحسابات المتصلة
                            <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full text-xs font-bold" x-text="connectedPlatforms.length"></span>
                        </h4>

                        <!-- Empty State -->
                        <template x-if="connectedPlatforms.length === 0">
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-8 text-center">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-unlink text-2xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium mb-2">لا توجد حسابات متصلة</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">{{ __("social.connect_accounts_first") }}</p>
                            </div>
                        </template>

                        <template x-for="platform in connectedPlatforms" :key="platform.id">
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                <!-- Platform Header - RTL Optimized -->
                                <div class="p-4 sm:p-5 flex items-center gap-4">
                                    <!-- Platform Icon -->
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0"
                                         :class="{
                                             'bg-gradient-to-br from-blue-500 to-blue-600': platform.type === 'facebook',
                                             'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400': platform.type === 'instagram',
                                             'bg-gradient-to-br from-sky-400 to-sky-500': platform.type === 'twitter',
                                             'bg-gradient-to-br from-blue-600 to-blue-700': platform.type === 'linkedin',
                                             'bg-gradient-to-br from-black to-gray-800': platform.type === 'tiktok'
                                         }">
                                        <i :class="{
                                            'fab fa-facebook-f': platform.type === 'facebook',
                                            'fab fa-instagram': platform.type === 'instagram',
                                            'fab fa-twitter': platform.type === 'twitter',
                                            'fab fa-linkedin-in': platform.type === 'linkedin',
                                            'fab fa-tiktok': platform.type === 'tiktok'
                                        }" class="text-white text-xl"></i>
                                    </div>

                                    <!-- Platform Info -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 dark:text-white truncate text-base" x-text="platform.name"></h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize" x-text="platform.type"></p>
                                    </div>

                                    <!-- Enable Toggle - RTL Optimized -->
                                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 group">
                                        <span class="ms-3 text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors hidden sm:inline">
                                            تفعيل
                                        </span>
                                        <input type="checkbox" class="sr-only peer"
                                               :checked="getQueueSetting(platform.integrationId, 'enabled')"
                                               @change="toggleQueue(platform.integrationId)">
                                        <div class="w-14 h-8 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer peer-checked:after:-translate-x-full rtl:peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:right-[4px] rtl:after:right-auto rtl:after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all after:shadow-sm peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-indigo-600"></div>
                                    </label>
                                </div>

                                <!-- Queue Settings (shown when enabled) - Enhanced -->
                                <div x-show="getQueueSetting(platform.integrationId, 'enabled')"
                                     x-collapse
                                     class="border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                                    <div class="p-4 sm:p-5 space-y-5">

                                        <!-- Posting Times - Enhanced -->
                                        <div x-data="{ times: ['09:00', '13:00', '18:00'] }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xs"></i>
                                                </div>
                                                {{ __("social.daily_publish_times") }}
                                            </label>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="(time, index) in times" :key="index">
                                                    <div class="group flex items-center gap-2 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl px-3 py-2 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                                                        <i class="fas fa-clock text-purple-500 dark:text-purple-400 text-sm"></i>
                                                        <input type="time" x-model="times[index]"
                                                               class="border-0 bg-transparent text-sm font-medium text-gray-800 dark:text-gray-200 focus:ring-0 w-20 text-center p-0">
                                                        <button @click="times.splice(index, 1)"
                                                                class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all opacity-0 group-hover:opacity-100">
                                                            <i class="fas fa-times text-xs"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <button @click="times.push('12:00')"
                                                        class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-purple-400 dark:hover:border-purple-500 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all">
                                                    <i class="fas fa-plus text-xs"></i>
                                                    إضافة
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Days of Week - Enhanced with circular buttons -->
                                        <div x-data="{ days: [1, 2, 3, 4, 5] }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-calendar-week text-indigo-600 dark:text-indigo-400 text-xs"></i>
                                                </div>
                                                {{ __("social.publish_days") }}
                                            </label>
                                            <div class="flex flex-wrap gap-2 sm:gap-3">
                                                <template x-for="day in [{v: 0, l: "{{ __("social.days.sunday") }}", s: 'ح'}, {v: 1, l: "{{ __("social.days.monday") }}", s: 'ن'}, {v: 2, l: "{{ __("social.days.tuesday") }}", s: 'ث'}, {v: 3, l: "{{ __("social.days.wednesday") }}", s: 'ر'}, {v: 4, l: "{{ __("social.days.thursday") }}", s: 'خ'}, {v: 5, l: "{{ __("social.days.friday") }}", s: 'ج'}, {v: 6, l: "{{ __("social.days.saturday") }}", s: 'س'}]" :key="day.v">
                                                    <button @click="days.includes(day.v) ? days.splice(days.indexOf(day.v), 1) : days.push(day.v)"
                                                            :class="days.includes(day.v)
                                                                ? 'bg-gradient-to-br from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30 scale-105'
                                                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                                                            class="w-12 h-12 sm:w-auto sm:h-auto sm:px-4 sm:py-2.5 rounded-xl text-sm font-bold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                                        <span class="hidden sm:inline" x-text="day.l"></span>
                                                        <span class="sm:hidden" x-text="day.s"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Posts Per Day - Enhanced with stepper -->
                                        <div x-data="{ postsPerDay: 3 }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-layer-group text-green-600 dark:text-green-400 text-xs"></i>
                                                </div>
                                                {{ __("social.posts_per_day") }}
                                            </label>
                                            <div class="inline-flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                                                <button @click="postsPerDay = Math.max(1, postsPerDay - 1)"
                                                        class="w-12 h-12 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-l border-gray-200 dark:border-gray-700"
                                                        :disabled="postsPerDay <= 1"
                                                        :class="{ 'opacity-50 cursor-not-allowed': postsPerDay <= 1 }">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <div class="w-16 h-12 flex items-center justify-center">
                                                    <span class="text-xl font-bold text-gray-900 dark:text-white tabular-nums" x-text="postsPerDay"></span>
                                                </div>
                                                <button @click="postsPerDay = Math.min(20, postsPerDay + 1)"
                                                        class="w-12 h-12 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-r border-gray-200 dark:border-gray-700"
                                                        :disabled="postsPerDay >= 20"
                                                        :class="{ 'opacity-50 cursor-not-allowed': postsPerDay >= 20 }">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ __("social.max_posts_per_day") }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer - Enhanced with clear action hierarchy -->
            <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col-reverse sm:flex-row justify-between items-center gap-3">
                <!-- Secondary Action -->
                <button @click="showQueueSettings = false"
                        class="w-full sm:w-auto px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600">
                    <i class="fas fa-times ms-2 text-sm"></i>
                    إغلاق
                </button>

                <!-- Primary Action -->
                <button @click="saveAllQueueSettings()"
                        class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/30 transition-all focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span>حفظ الإعدادات</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function socialManager() {
    return {
        // Posts list state
        posts: [],
        filterPlatform: 'all',
        statusFilter: 'all',
        filterPostType: 'all',
        searchQuery: '',
        sortBy: 'newest',
        viewMode: 'grid',
        scheduledCount: 0,
        publishedCount: 0,
        draftCount: 0,
        failedCount: 0,
        selectedPosts: [],

        // Calendar state
        currentDate: new Date(),

        // New post state (used by global publish modal)
        loadingPlatforms: false,
        connectedPlatforms: [],
        selectedPlatformIds: [],
        isSubmitting: false,
        dragOver: false,
        uploadedMedia: [],
        showAiAssistant: false,
        previewPlatform: 'facebook',
        newPost: {
            content: '',
            publishType: 'now',
            scheduledDate: '',
            scheduledTime: '',
            postType: 'feed', // Default post type
            firstComment: '', // First comment for Instagram/Facebook
            location: '', // Location tag
            locationId: null, // Location ID for API
        },

        // Input helper variables
        collaboratorInput: '',
        productTagInput: '',
        userTagInput: '',

        // Location autocomplete state
        locationQuery: '',
        locationResults: [],
        showLocationDropdown: false,
        isSearchingLocations: false,
        selectedLocation: null,
        locationSearchTimeout: null,

        // Collaborator suggestions state
        collaboratorSuggestions: [],
        showCollaboratorSuggestions: false,
        isValidatingUsername: false,
        usernameValidationResult: null,
        usernameValidationTimeout: null,
        validatedUserInfo: null,

        // Product details state
        showProductDetails: false,
        currencies: [
            { code: 'SAR', symbol: 'ر.س', name: 'ريال سعودي' },
            { code: 'AED', symbol: 'د.إ', name: 'درهم إماراتي' },
            { code: 'USD', symbol: '$', name: 'دولار أمريكي' },
            { code: 'EUR', symbol: '€', name: 'يورو' },
            { code: 'GBP', symbol: '£', name: 'جنيه إسترليني' },
            { code: 'EGP', symbol: 'ج.م', name: 'جنيه مصري' },
            { code: 'KWD', symbol: 'د.ك', name: 'دينار كويتي' },
            { code: 'QAR', symbol: 'ر.ق', name: 'ريال قطري' },
            { code: 'BHD', symbol: 'د.ب', name: 'دينار بحريني' },
            { code: 'OMR', symbol: 'ر.ع', name: 'ريال عماني' },
        ],

        // Post type specific options (API-supported only)
        postOptions: {
            // Instagram/Facebook API-Supported Options
            instagram: {
                location: '', // Location name (API: location_id)
                locationId: '', // Facebook Places ID
                userTags: [], // Tagged users [{username, x, y}] (API: user_tags)
                collaborators: [], // Collaborators up to 3 (API: collaborators)
                productTags: [], // Product tags for shopping posts
                altText: '', // Alt text for accessibility (API: alt_text)
                firstComment: '', // Auto-post first comment (API: /comments endpoint)
            },

            // Product Details (for DM-based orders - no Instagram Shopping required)
            product: {
                enabled: false,
                title: '',
                price: '',
                currency: 'SAR',
                description: '',
                orderMessage: 'للطلب، أرسل رسالة مباشرة 📩', // Default CTA
            },

            // Reel API-Supported Options
            reel: {
                coverType: 'frame', // 'frame' or 'custom'
                coverFrameOffset: 0, // milliseconds (API: thumb_offset)
                coverImageUrl: '', // custom cover (API: cover_url)
                shareToFeed: true, // Show reel in feed (API: share_to_feed)
            },

            // Story Options (limited API support)
            story: {
                duration: 5, // seconds per slide (for reference only)
            },

            // Carousel API-Supported Options
            carousel: {
                altTexts: [], // Alt text for each image (API: alt_text per child)
            },

            // TikTok Content Posting API Options
            tiktok: {
                viewerSetting: 'public', // API: privacy_level (PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, SELF_ONLY)
                disableComments: false, // API: disable_comment
                disableDuet: false, // API: disable_duet
                disableStitch: false, // API: disable_stitch
                brandContentToggle: false, // API: brand_content_toggle
                aiGenerated: false, // API: ai_disclosure
            },

            // LinkedIn Posts API Options
            linkedin: {
                visibility: 'PUBLIC', // API: visibility (PUBLIC, CONNECTIONS)
                articleTitle: '', // API: article title
                articleDescription: '', // API: article description
                allowComments: true, // API: allowComments
            },

            // Twitter/X API Options
            twitter: {
                threadTweets: [''], // Multiple tweets for thread
                replyRestriction: 'everyone', // API: reply_settings (everyone, mentionedUsers, following)
                altText: '', // API: alt_text for media
            },
        },

        // Post types configuration
        allPostTypes: {
            'facebook': [
                {value: 'feed', label: \'{{ __("social.post_types.feed") }}\', icon: 'fa-newspaper'},
                {value: 'reel', label: \'{{ __("social.post_types.reel") }}\', icon: 'fa-video'},
                {value: 'story', label: \'{{ __("social.post_types.story") }}\', icon: 'fa-circle'}
            ],
            'instagram': [
                {value: 'feed', label: \'{{ __("social.post_types.feed") }}\', icon: 'fa-image'},
                {value: 'reel', label: \'{{ __("social.post_types.reel") }}\', icon: 'fa-video'},
                {value: 'story', label: \'{{ __("social.post_types.story") }}\', icon: 'fa-circle'},
                {value: 'carousel', label: \'{{ __("social.post_types.carousel") }}\', icon: 'fa-images'}
            ],
            'twitter': [
                {value: 'tweet', label: 'تغريدة (Tweet)', icon: 'fa-comment'},
                {value: 'thread', label: 'سلسلة (Thread)', icon: 'fa-list'}
            ],
            'linkedin': [
                {value: 'post', label: \'{{ __("social.post_types.post") }}\', icon: 'fa-file-alt'},
                {value: 'article', label: 'مقال (Article)', icon: 'fa-newspaper'}
            ]
        },

        // Queue settings modal
        showQueueSettings: false,
        queueSettings: [],

        // Best times suggestions
        bestTimes: [
            { label: 'صباحاً', value: '09:00', engagement: '+23%' },
            { label: 'ظهراً', value: '12:00', engagement: '+18%' },
            { label: 'مساءً', value: '18:00', engagement: '+31%' },
            { label: 'ليلاً', value: '21:00', engagement: '+15%' }
        ],

        // Edit post modal state
        showEditPostModal: false,
        editingPost: {
            id: null,
            content: '',
            platform: '',
            status: '',
            scheduled_at: null,
            media: [],
            account_username: ''
        },
        isUpdating: false,
        isDeletingFailed: false,

        // Get the org ID from the URL
        get orgId() {
            const match = window.location.pathname.match(/\/orgs\/([^\/]+)/);
            return match ? match[1] : null;
        },

        // Minimum date for scheduling (today)
        get minDate() {
            return new Date().toISOString().split('T')[0];
        },

        // Check platform selections
        get hasInstagramSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'instagram'
            );
        },

        get hasTwitterSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'twitter'
            );
        },

        get hasFacebookSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'facebook'
            );
        },

        get hasTikTokSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'tiktok'
            );
        },

        get hasLinkedInSelected() {
            return this.selectedPlatformIds.some(id =>
                this.connectedPlatforms.find(p => p.id === id)?.type === 'linkedin'
            );
        },

        // Get available post types based on selected platforms
        get availablePostTypes() {
            if (this.selectedPlatformIds.length === 0) {
                return [];
            }

            // Get unique platforms from selected platform IDs
            const selectedPlatforms = this.connectedPlatforms
                .filter(p => this.selectedPlatformIds.includes(p.id))
                .map(p => p.type);

            const uniquePlatforms = [...new Set(selectedPlatforms)];

            // If multiple platforms selected, show common post types
            if (uniquePlatforms.length > 1) {
                // Find common post types across all selected platforms
                const platformPostTypes = uniquePlatforms.map(platform =>
                    this.allPostTypes[platform] || []
                );

                // Get the intersection of all post types (common across all platforms)
                if (platformPostTypes.length === 0) {
                    return [{value: 'feed', label: \'{{ __("social.post_types.feed") }}\', icon: 'fa-newspaper'}];
                }

                // Find post types that exist in all platforms
                const commonPostTypes = platformPostTypes[0].filter(postType =>
                    platformPostTypes.every(types =>
                        types.some(t => t.value === postType.value)
                    )
                );

                // If no common types, default to feed
                return commonPostTypes.length > 0
                    ? commonPostTypes
                    : [{value: 'feed', label: \'{{ __("social.post_types.feed") }}\', icon: 'fa-newspaper'}];
            }

            // Single platform selected, return its specific post types
            const platform = uniquePlatforms[0];
            return this.allPostTypes[platform] || [{value: 'feed', label: \'{{ __("social.post_types.feed") }}\', icon: 'fa-newspaper'}];
        },

        get selectedPlatformsForPreview() {
            return this.connectedPlatforms.filter(p => this.selectedPlatformIds.includes(p.id));
        },

        // Can submit the form
        get canSubmit() {
            const hasContent = this.newPost.content.trim().length > 0;
            const hasPlatforms = this.selectedPlatformIds.length > 0;
            const hasScheduleIfNeeded = this.newPost.publishType !== 'scheduled' ||
                                        (this.newPost.scheduledDate && this.newPost.scheduledTime);
            const hasMediaForInstagram = !this.hasInstagramSelected || this.uploadedMedia.length > 0;
            return hasContent && hasPlatforms && hasScheduleIfNeeded && hasMediaForInstagram;
        },

        // Unique platforms from posts (for dynamic platform filter)
        get uniquePlatforms() {
            const platforms = [...new Set(this.posts.map(p => p.platform).filter(Boolean))];
            return platforms.sort();
        },

        // Helper methods for platform display
        getPlatformIcon(platform) {
            const icons = {
                'facebook': 'fab fa-facebook',
                'instagram': 'fab fa-instagram',
                'twitter': 'fab fa-twitter',
                'x': 'fab fa-x-twitter',
                'linkedin': 'fab fa-linkedin',
                'youtube': 'fab fa-youtube',
                'tiktok': 'fab fa-tiktok',
                'pinterest': 'fab fa-pinterest',
                'reddit': 'fab fa-reddit',
                'tumblr': 'fab fa-tumblr',
                'threads': 'fab fa-at',
                'google_business': 'fab fa-google'
            };
            return icons[platform] || 'fas fa-share-alt';
        },

        getPlatformName(platform) {
            const names = {
                'facebook': 'Facebook',
                'instagram': 'Instagram',
                'twitter': 'Twitter',
                'x': 'X',
                'linkedin': 'LinkedIn',
                'youtube': 'YouTube',
                'tiktok': 'TikTok',
                'pinterest': 'Pinterest',
                'reddit': 'Reddit',
                'tumblr': 'Tumblr',
                'threads': 'Threads',
                'google_business': 'Google Business'
            };
            return names[platform] || platform;
        },

        getPlatformFilterClass(platform, active) {
            if (!active) return 'bg-gray-100 text-gray-700 hover:bg-gray-200';
            const classes = {
                'facebook': 'bg-blue-600 text-white',
                'instagram': 'bg-gradient-to-r from-purple-600 to-pink-600 text-white',
                'twitter': 'bg-sky-500 text-white',
                'x': 'bg-black text-white',
                'linkedin': 'bg-blue-700 text-white',
                'youtube': 'bg-red-600 text-white',
                'tiktok': 'bg-black text-white',
                'pinterest': 'bg-red-700 text-white',
                'reddit': 'bg-orange-600 text-white',
                'tumblr': 'bg-indigo-800 text-white',
                'threads': 'bg-black text-white',
                'google_business': 'bg-blue-500 text-white'
            };
            return classes[platform] || 'bg-gray-600 text-white';
        },

        // Sorted and filtered posts
        get sortedFilteredPosts() {
            let filtered = this.posts.filter(post => {
                const platformMatch = this.filterPlatform === 'all' || post.platform === this.filterPlatform;
                const statusMatch = this.statusFilter === 'all' || post.status === this.statusFilter;
                const postTypeMatch = this.filterPostType === 'all' || post.post_type === this.filterPostType;
                const searchMatch = !this.searchQuery ||
                    (post.post_text && post.post_text.toLowerCase().includes(this.searchQuery.toLowerCase()));
                return platformMatch && statusMatch && postTypeMatch && searchMatch;
            });

            // Sort
            switch(this.sortBy) {
                case 'oldest':
                    filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                    break;
                case 'scheduled':
                    filtered.sort((a, b) => {
                        if (!a.scheduled_at) return 1;
                        if (!b.scheduled_at) return -1;
                        return new Date(a.scheduled_at) - new Date(b.scheduled_at);
                    });
                    break;
                case 'platform':
                    filtered.sort((a, b) => (a.platform || '').localeCompare(b.platform || ''));
                    break;
                default: // newest
                    filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }

            return filtered;
        },

        // Calendar helpers
        get currentMonthYear() {
            return this.currentDate.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
        },

        get calendarDays() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const days = [];

            // Previous month days
            const firstDayOfWeek = firstDay.getDay();
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const date = new Date(year, month, -i);
                days.push(this.createDayObject(date, false));
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(year, month, i);
                days.push(this.createDayObject(date, true));
            }

            // Next month days to fill grid
            const remaining = 42 - days.length;
            for (let i = 1; i <= remaining; i++) {
                const date = new Date(year, month + 1, i);
                days.push(this.createDayObject(date, false));
            }

            return days;
        },

        createDayObject(date, isCurrentMonth) {
            const dateStr = date.toISOString().split('T')[0];
            const today = new Date().toISOString().split('T')[0];
            return {
                date: dateStr,
                dayNumber: date.getDate(),
                isCurrentMonth,
                isToday: dateStr === today,
                posts: this.posts.filter(p => {
                    const postDate = p.scheduled_at || p.published_at || p.created_at;
                    return postDate && postDate.startsWith(dateStr);
                })
            };
        },

        changeMonth(delta) {
            this.currentDate = new Date(
                this.currentDate.getFullYear(),
                this.currentDate.getMonth() + delta,
                1
            );
        },

        async init() {
            // Debug: v2025.11.26.1040 - Added dynamic filters
            console.log('[CMIS Social] v1.0.2 - Initializing, orgId:', this.orgId);

            // Load posts, connected platforms, and collaborator suggestions in parallel
            await Promise.all([
                this.fetchPosts(),
                this.loadConnectedPlatforms(),
                this.loadCollaboratorSuggestions()
            ]);
            console.log('[CMIS Social] Posts loaded:', this.posts.length, 'posts');
            console.log('[CMIS Social] Platforms loaded:', this.connectedPlatforms.length, 'platforms');
            console.log('[CMIS Social] Collaborator suggestions loaded:', this.collaboratorSuggestions.length);

            // Set default schedule time to tomorrow 10 AM
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';

            // Update preview platform when selection changes
            this.$watch('selectedPlatformIds', (ids) => {
                if (ids.length > 0) {
                    const platform = this.connectedPlatforms.find(p => ids.includes(p.id));
                    if (platform) this.previewPlatform = platform.type;
                }
            });
        },

        async fetchPosts() {
            console.log('[CMIS Social] fetchPosts() called, orgId:', this.orgId);
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                console.log('[CMIS Social] API response status:', response.status);
                const result = await response.json();
                console.log('[CMIS Social] API result:', result);

                if (result.success && result.data) {
                    this.posts = result.data.data || result.data || [];
                    console.log('[CMIS Social] Posts set from result.data.data:', this.posts.length);
                } else if (Array.isArray(result.data)) {
                    this.posts = result.data;
                    console.log('[CMIS Social] Posts set from result.data array:', this.posts.length);
                } else {
                    this.posts = [];
                    console.log('[CMIS Social] Posts set to empty array');
                }
                this.updateCounts();
            } catch (error) {
                console.error('[CMIS Social] Failed to fetch posts:', error);
                this.posts = [];
            }
        },

        async loadConnectedPlatforms() {
            this.loadingPlatforms = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/accounts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });
                const data = await response.json();

                if (data.success && data.data?.accounts) {
                    this.connectedPlatforms = data.data.accounts.map(account => ({
                        id: account.id,
                        type: account.type,
                        name: account.name,
                        platformId: account.platformId,
                        picture: account.picture,
                        username: account.username,
                        connectionId: account.connectionId,
                        pageId: account.type === 'facebook' ? account.platformId : null,
                        accountId: account.type === 'instagram' ? account.platformId : null,
                    }));
                } else {
                    this.connectedPlatforms = [];
                }
            } catch (error) {
                console.error('Failed to load platforms:', error);
                this.connectedPlatforms = [];
            } finally {
                this.loadingPlatforms = false;
            }
        },

        togglePlatformSelection(platform) {
            const index = this.selectedPlatformIds.indexOf(platform.id);
            if (index === -1) {
                this.selectedPlatformIds.push(platform.id);
            } else {
                this.selectedPlatformIds.splice(index, 1);
            }
        },

        togglePostSelection(postId) {
            const index = this.selectedPosts.indexOf(postId);
            if (index === -1) {
                this.selectedPosts.push(postId);
            } else {
                this.selectedPosts.splice(index, 1);
            }
        },

        toggleAllPosts(event) {
            if (event.target.checked) {
                this.selectedPosts = this.sortedFilteredPosts.map(p => p.post_id);
            } else {
                this.selectedPosts = [];
            }
        },

        async bulkDelete() {
            if (!confirm(__("social.confirm_delete_posts", {count: this.selectedPosts.length}))) return;

            for (const postId of this.selectedPosts) {
                await this.deletePost(postId, false);
            }
            this.selectedPosts = [];
            await this.fetchPosts();
            if (window.notify) {
                window.notify(__("social.posts_deleted_success"), 'success');
            }
        },

        setBestTime(time) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = time.value;
        },

        async aiSuggest(type) {
            const content = this.newPost.content;
            if (!content) {
                if (window.notify) {
                    window.notify(__("social.write_content_first"), 'warning');
                }
                return;
            }

            // Show loading state
            const loadingMessage = {
                'shorter': 'جاري الاختصار...',
                'longer': 'جاري التوسع...',
                'formal': 'جاري تحويل الأسلوب...',
                'casual': 'جاري تحويل الأسلوب...',
                'hashtags': __("social.generating_hashtags"),
                'emojis': 'جاري إضافة الإيموجي...',
            }[type] || 'جاري المعالجة...';

            if (window.notify) {
                window.notify(loadingMessage, 'info');
            }

            // Disable the button temporarily
            const originalContent = this.newPost.content;

            try {
                // Call the AI API
                const response = await fetch(`/orgs/${this.orgId}/social/ai/transform-content`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        content: content,
                        type: type,
                        platform: 'general'
                    })
                });

                const data = await response.json();

                if (response.ok && data.success && data.data.transformed) {
                    this.newPost.content = data.data.transformed;
                    if (window.notify) {
                        window.notify('تم التحويل بنجاح!', 'success');
                    }
                } else {
                    // Show detailed validation errors for debugging
                    let errorMessage = data.message || __("social.conversion_failed");
                    if (data.errors) {
                        const errorDetails = Object.values(data.errors).flat().join(', ');
                        errorMessage += ': ' + errorDetails;
                    }
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('AI transformation error:', error);
                if (window.notify) {
                    window.notify('حدث خطأ في مساعد AI: ' + error.message, 'error');
                }
                // Restore original content on error
                this.newPost.content = originalContent;
            }
        },

        duplicatePost(post) {
            // Dispatch event to open global publish modal with pre-filled content
            window.dispatchEvent(new CustomEvent('open-publish-modal', {
                detail: { content: post.post_text || post.content || '' }
            }));
            if (window.notify) {
                window.notify(__("social.content_copied_success"), 'success');
            }
        },

        handleFileSelect(event) {
            const files = Array.from(event.target.files);
            this.processFiles(files);
        },

        handleFileDrop(event) {
            this.dragOver = false;
            const files = Array.from(event.dataTransfer.files);
            this.processFiles(files);
        },

        processFiles(files) {
            files.forEach(file => {
                if (file.type.startsWith('image/') || file.type.startsWith('video/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.uploadedMedia.push({
                            file: file,
                            preview: e.target.result,
                            type: file.type.startsWith('image/') ? 'image' : 'video'
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });
        },

        removeMedia(index) {
            this.uploadedMedia.splice(index, 1);
        },

        // Location Search Methods
        searchLocations() {
            // Clear previous timeout
            if (this.locationSearchTimeout) {
                clearTimeout(this.locationSearchTimeout);
            }

            // Don't search if query is too short
            if (this.locationQuery.length < 2) {
                this.locationResults = [];
                this.showLocationDropdown = false;
                return;
            }

            // Debounce search by 300ms
            this.locationSearchTimeout = setTimeout(async () => {
                this.isSearchingLocations = true;
                try {
                    const response = await fetch(`/api/orgs/${this.orgId}/social/locations?query=${encodeURIComponent(this.locationQuery)}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        }
                    });

                    const result = await response.json();

                    if (response.ok && result.data) {
                        this.locationResults = result.data;
                        this.showLocationDropdown = true;
                    } else {
                        this.locationResults = [];
                        console.warn('Location search failed:', result.message);
                    }
                } catch (error) {
                    console.error('Location search error:', error);
                    this.locationResults = [];
                } finally {
                    this.isSearchingLocations = false;
                }
            }, 300);
        },

        selectLocation(location) {
            this.selectedLocation = location;
            this.postOptions.instagram.location = location.name;
            this.postOptions.instagram.locationId = location.id;
            this.locationQuery = '';
            this.locationResults = [];
            this.showLocationDropdown = false;
        },

        clearLocation() {
            this.selectedLocation = null;
            this.postOptions.instagram.location = '';
            this.postOptions.instagram.locationId = '';
            this.locationQuery = '';
        },

        // Collaborator Methods
        async loadCollaboratorSuggestions() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/collaborators/suggestions`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    this.collaboratorSuggestions = result.data?.collaborators || [];
                }
            } catch (error) {
                console.error('Failed to load collaborator suggestions:', error);
            }
        },

        get filteredCollaboratorSuggestions() {
            const input = this.collaboratorInput?.replace('@', '').toLowerCase() || '';
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());

            return this.collaboratorSuggestions.filter(s => {
                const lowerS = s.toLowerCase();
                return !existing.includes(lowerS) &&
                       (input === '' || lowerS.includes(input));
            }).slice(0, 5);
        },

        searchCollaborators() {
            // Show suggestions dropdown when typing
            this.showCollaboratorSuggestions = true;
            this.usernameValidationResult = null;

            // Debounce validation
            if (this.usernameValidationTimeout) {
                clearTimeout(this.usernameValidationTimeout);
            }

            const username = this.collaboratorInput?.replace('@', '').trim();
            if (username && username.length >= 2) {
                this.usernameValidationTimeout = setTimeout(() => {
                    this.validateUsername(username);
                }, 800);
            }
        },

        async validateUsername(username) {
            if (!username || username.length < 2) return;

            this.isValidatingUsername = true;
            this.usernameValidationResult = null;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/instagram/validate-username`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({ username: username })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.usernameValidationResult = result.data?.valid || false;
                    this.validatedUserInfo = result.data?.user || null;
                } else {
                    this.usernameValidationResult = null;
                }
            } catch (error) {
                console.error('Username validation failed:', error);
                this.usernameValidationResult = null;
            } finally {
                this.isValidatingUsername = false;
            }
        },

        async addCollaborator(username) {
            if (!username) return;

            // Clean the username
            username = username.replace('@', '').trim();
            if (!username) return;

            // Check if already added
            const existing = this.postOptions.instagram.collaborators.map(c => c.replace('@', '').toLowerCase());
            if (existing.includes(username.toLowerCase())) {
                this.collaboratorInput = '';
                this.showCollaboratorSuggestions = false;
                return;
            }

            // Check limit (max 3)
            if (this.postOptions.instagram.collaborators.length >= 3) {
                if (window.notify) {
                    window.notify('يمكنك إضافة 3 متعاونين كحد أقصى', 'warning');
                }
                return;
            }

            // Add to list
            this.postOptions.instagram.collaborators.push(username);
            this.collaboratorInput = '';
            this.showCollaboratorSuggestions = false;
            this.usernameValidationResult = null;

            // Store for future suggestions (async, don't wait)
            fetch(`/api/orgs/${this.orgId}/social/collaborators`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ username: username })
            }).catch(() => {}); // Silently ignore errors
        },

        async savePost() {
            if (!this.canSubmit || this.isSubmitting) return;
            this.isSubmitting = true;

            try {
                const formData = new FormData();
                formData.append('content', this.newPost.content);
                formData.append('publish_type', this.newPost.publishType);
                formData.append('post_type', this.newPost.postType); // Add post type

                const selectedPlatforms = this.connectedPlatforms.filter(p =>
                    this.selectedPlatformIds.includes(p.id)
                );
                formData.append('platforms', JSON.stringify(selectedPlatforms));

                // Add all post options as JSON
                formData.append('post_options', JSON.stringify(this.postOptions));

                // Add location if set
                if (this.newPost.location) {
                    formData.append('location', this.newPost.location);
                }

                // Add first comment if set
                if (this.newPost.firstComment) {
                    formData.append('first_comment', this.newPost.firstComment);
                }

                if (this.newPost.publishType === 'scheduled') {
                    formData.append('scheduled_at', `${this.newPost.scheduledDate}T${this.newPost.scheduledTime}:00`);
                }

                this.uploadedMedia.forEach((media, index) => {
                    formData.append(`media[${index}]`, media.file);
                });

                const response = await fetch(`/api/orgs/${this.orgId}/social/posts`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok) {
                    const messages = {
                        'now': __("social.post_published_success"),
                        'scheduled': __("social.post_scheduled_success"),
                        'queue': __("social.post_queued_success"),
                        'draft': __("social.draft_saved_success")
                    };
                    if (window.notify) {
                        window.notify(messages[this.newPost.publishType], 'success');
                    }
                    this.resetNewPost();
                    await this.fetchPosts();
                } else {
                    throw new Error(result.message || __("social.post_save_failed"));
                }
            } catch (error) {
                console.error('Failed to save post:', error);
                if (window.notify) {
                    window.notify(error.message || __("social.post_save_failed"), 'error');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        resetNewPost() {
            this.newPost = {
                content: '',
                publishType: 'now',
                scheduledDate: '',
                scheduledTime: ''
            };
            this.selectedPlatformIds = [];
            this.uploadedMedia = [];
            this.showAiAssistant = false;

            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.newPost.scheduledDate = tomorrow.toISOString().split('T')[0];
            this.newPost.scheduledTime = '10:00';
        },

        updateCounts() {
            this.scheduledCount = this.posts.filter(p => p.status === 'scheduled').length;
            this.publishedCount = this.posts.filter(p => p.status === 'published').length;
            this.draftCount = this.posts.filter(p => p.status === 'draft').length;
            this.failedCount = this.posts.filter(p => p.status === 'failed').length;
        },

        getStatusLabel(status) {
            const labels = {
                'scheduled': '{{ __('social.scheduled_status') }}',
                'published': '{{ __('social.published_status') }}',
                'draft': '{{ __('social.draft_status') }}',
                'failed': '{{ __('social.failed_status') }}'
            };
            return labels[status] || status;
        },

        formatDate(date) {
            if (!date) return '';
            return new Date(date).toLocaleString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        editPost(post) {
            this.editingPost = {
                id: post.post_id || post.id,
                content: post.content || post.post_text || '',
                platform: post.platform,
                status: post.status,
                scheduled_at: post.scheduled_at,
                media: post.media || [],
                account_username: post.account_username,
                scheduledDate: '',
                scheduledTime: ''
            };

            if (post.scheduled_at) {
                const scheduled = new Date(post.scheduled_at);
                this.editingPost.scheduledDate = scheduled.toISOString().split('T')[0];
                this.editingPost.scheduledTime = scheduled.toTimeString().slice(0, 5);
            }

            this.showEditPostModal = true;
        },

        async updatePost() {
            if (this.isUpdating || !this.editingPost.content.trim()) return;
            this.isUpdating = true;

            try {
                const updateData = {
                    content: this.editingPost.content,
                };

                if ((this.editingPost.status === 'draft' || this.editingPost.status === 'scheduled')
                    && this.editingPost.scheduledDate && this.editingPost.scheduledTime) {
                    updateData.scheduled_at = `${this.editingPost.scheduledDate}T${this.editingPost.scheduledTime}:00`;
                    updateData.status = 'scheduled';
                }

                const response = await fetch(`/orgs/${this.orgId}/social/posts/${this.editingPost.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(updateData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.showEditPostModal = false;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(__("social.post_updated_success"), 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || __("social.post_update_failed"), 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to update post:', error);
                if (window.notify) {
                    window.notify(__("social.post_update_failed"), 'error');
                }
            } finally {
                this.isUpdating = false;
            }
        },

        async publishNow(postId) {
            if (!confirm(__("social.confirm_publish_now"))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(__("social.post_published_success"), "success");
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || __("social.post_publish_failed"), 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to publish post:', error);
                if (window.notify) {
                    window.notify(__("social.post_publish_failed"), 'error');
                }
            }
        },

        async retryPost(postId) {
            if (!confirm(__("social.confirm_retry_publish"))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/social/posts/${postId}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(__("social.post_published_success"), 'success');
                    }
                } else {
                    if (window.notify) {
                        window.notify(__("social.retry_failed") + ': ' + (result.message || ''), 'error');
                    }
                    await this.fetchPosts();
                }
            } catch (error) {
                console.error('Failed to retry post:', error);
                if (window.notify) {
                    window.notify(__("social.retry_failed"), 'error');
                }
            }
        },

        async deletePost(postId, showConfirm = true) {
            if (showConfirm && !confirm(__("social.confirm_delete_post"))) return;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts/${postId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (showConfirm) {
                        await this.fetchPosts();
                        if (window.notify) {
                            window.notify(__("social.post_deleted_success"), "success");
                        }
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || __("social.post_delete_failed"), 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete post:', error);
                if (window.notify) {
                    window.notify(__("social.post_delete_failed"), 'error');
                }
            }
        },

        async deleteAllFailed() {
            if (!confirm(__("social.confirm_delete_failed_posts", {count: this.failedCount}))) return;

            this.isDeletingFailed = true;

            try {
                const response = await fetch(`/orgs/${this.orgId}/social/posts-failed`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const deletedCount = result.data?.deleted_count || 0;
                    await this.fetchPosts();
                    if (window.notify) {
                        window.notify(__("social.failed_posts_deleted_success", {count: deletedCount}), "success");
                    }
                } else {
                    if (window.notify) {
                        window.notify(result.message || __("social.failed_posts_delete_failed"), 'error');
                    }
                }
            } catch (error) {
                console.error('Failed to delete all failed posts:', error);
                if (window.notify) {
                    window.notify(__("social.failed_posts_delete_failed"), 'error');
                }
            } finally {
                this.isDeletingFailed = false;
            }
        },

        // Queue Settings Methods
        getQueueSetting(integrationId, key) {
            const setting = this.queueSettings.find(s => s.integration_id === integrationId);
            if (!setting) return key === 'enabled' ? false : null;

            switch(key) {
                case 'enabled': return setting.queue_enabled;
                case 'times': return setting.posting_times || [];
                case 'days': return setting.days_enabled || [1,2,3,4,5];
                case 'count': return setting.posts_per_day || 3;
                default: return null;
            }
        },

        toggleQueue(integrationId) {
            const setting = this.queueSettings.find(s => s.integration_id === integrationId);
            if (setting) {
                setting.queue_enabled = !setting.queue_enabled;
            } else {
                // Create new setting with defaults
                this.queueSettings.push({
                    integration_id: integrationId,
                    queue_enabled: true,
                    posting_times: ['09:00', '13:00', '18:00'],
                    days_enabled: [1, 2, 3, 4, 5],
                    posts_per_day: 3
                });
            }
        },

        async saveAllQueueSettings() {
            try {
                // Save each platform's queue settings
                const promises = this.queueSettings.map(setting => {
                    return fetch(`/orgs/${this.orgId}/social/queue-settings`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            integration_id: setting.integration_id,
                            queue_enabled: setting.queue_enabled,
                            posting_times: setting.posting_times,
                            days_enabled: setting.days_enabled,
                            posts_per_day: setting.posts_per_day
                        })
                    });
                });

                await Promise.all(promises);

                this.showQueueSettings = false;
                if (window.notify) {
                    window.notify('تم حفظ إعدادات الطابور بنجاح', 'success');
                }
            } catch (error) {
                console.error('Failed to save queue settings:', error);
                if (window.notify) {
                    window.notify(__("social.settings_save_failed"), 'error');
                }
            }
        }
    };
}
</script>
@endpush

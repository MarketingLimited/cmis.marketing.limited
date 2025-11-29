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
                       placeholder="{{ __("social.search_content") }}"
                       class="w-full pr-10 pl-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Platform Filter -->
                <select x-model="filters.platform" @change="loadPosts()"
                        class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <option value="">{{ __("social.all_platforms") }}</option>
                    <option value="instagram">{{ __("social.platforms.instagram") }}</option>
                    <option value="facebook">{{ __("social.platforms.facebook") }}</option>
                    <option value="twitter">{{ __("social.platforms.twitter") }}</option>
                    <option value="linkedin">{{ __("social.platforms.linkedin") }}</option>
                    <option value="tiktok">{{ __("social.platforms.tiktok") }}</option>
                </select>

                <!-- Analysis Status -->
                <select x-model="filters.is_analyzed" @change="loadPosts()"
                        class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <option value="">{{ __("social.all_statuses") }}</option>
                    <option value="1">{{ __("social.is_analyzed") }}</option>
                    <option value="0">{{ __("social.waiting_analysis") }}</option>
                </select>

                <!-- KB Status -->
                <select x-model="filters.is_in_kb" @change="loadPosts()"
                        class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <option value="">{{ __("social.knowledge_base") }}</option>
                    <option value="1">{{ __("social.kb_added") }}</option>
                    <option value="0">{{ __("social.kb_not_added") }}</option>
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
                        title="{{ __("social.reset_filters") }}">
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
                    {{ __("social.selected_count") }} <span class="font-bold text-blue-600" x-text="selectedPosts.length"></span>
                </span>
                <button @click="bulkAddToKB()" class="px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg text-sm font-medium transition">
                    {{ __("social.add_to_kb") }}
                </button>
                <button @click="bulkAnalyze()" class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-sm font-medium transition">
                    {{ __("social.analyze_selected") }}
                </button>
                <button @click="clearSelection()" class="px-4 py-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm transition">
                    {{ __("social.clear_selection") }}
                </button>
            </div>
        </div>
    </div>
</div>

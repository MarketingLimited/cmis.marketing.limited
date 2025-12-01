{{-- Controls Panel Component --}}
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
            <button @click="window.dispatchEvent(new CustomEvent('open-publish-modal'))"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-4 sm:px-6 py-2.5 rounded-xl font-medium shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span class="hidden sm:inline">{{ __('social.new_post') }}</span>
            </button>
        </div>
    </div>

    <!-- Platform Filters - Enhanced with dark mode and animations -->
    @include('social.components.platform-filters')

    <!-- Post Type Filter - Enhanced with dark mode -->
    @include('social.components.post-type-filters')

    <!-- Status Tabs with Bulk Actions - Enhanced -->
    @include('social.components.status-filters')
</div>

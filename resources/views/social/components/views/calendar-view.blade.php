{{-- Calendar View Component --}}
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
        <template x-for="day in dayNames">
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
                             :title="post.social_account_username || post.social_account_display_name || post.account_username || post.platform"
                             x-text="(post.social_account_username || post.social_account_display_name || post.account_username || post.platform).substring(0, 12) + (post.post_text ? ': ' + post.post_text.substring(0, 10) + '...' : '')"></div>
                    </template>
                    <div x-show="day.posts.length > 2" class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 text-center font-medium">
                        +<span x-text="day.posts.length - 2"></span> {{ __("social.more") }}
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

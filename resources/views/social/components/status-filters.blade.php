{{-- Status Filters with Bulk Actions Component --}}
<div class="flex gap-2 border-t border-gray-200 dark:border-gray-700 pt-4 items-center justify-between flex-wrap">
    <div class="flex gap-1.5 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
        <button @click="statusFilter = 'all'"
                :class="statusFilter === 'all' ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700' : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600'"
                class="px-4 py-2 rounded-xl font-medium transition-all duration-200 text-sm border whitespace-nowrap">
            {{ __('social.all') }} (<span x-text="posts.length" class="tabular-nums"></span>)
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

{{-- Empty State Component --}}
<template x-if="sortedFilteredPosts.length === 0 && viewMode === 'grid'">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 sm:p-16 text-center">
        <div class="w-20 h-20 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-indigo-500/10">
            <i class="fas fa-calendar-plus text-indigo-600 dark:text-indigo-400 text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">{{ __("social.no_posts_found") }}</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm mx-auto">{{ __("social.create_post_description") }}</p>
        <button @click="window.dispatchEvent(new CustomEvent('open-publish-modal'))"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-8 py-3.5 rounded-xl font-semibold shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/30 transition-all">
            <i class="fas fa-plus ms-2"></i>
            {{ __("social.create_new_post") }}
        </button>
    </div>
</template>

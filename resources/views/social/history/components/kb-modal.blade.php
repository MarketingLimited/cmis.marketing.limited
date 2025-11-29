<!-- Knowledge Base Modal -->
<div x-show="showKBModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showKBModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl p-6">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ __("social.knowledge_base") }}</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ __("social.kb_contains") }} <span class="font-bold text-purple-600" x-text="stats.inKB"></span> {{ __("social.posts_count") }}
                </p>
                <button @click="showKBModal = false"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl font-medium transition dark:text-white">
                    {{ __("social.close") }}
                </button>
            </div>
        </div>
    </div>
</div>

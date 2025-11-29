{{-- Post Type Filters Component --}}
<div class="flex flex-wrap gap-2 mb-4">
    <button @click="filterPostType = 'all'"
            :class="filterPostType === 'all' ? 'bg-gray-800 dark:bg-gray-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
        <i class="fas fa-th-large ms-1"></i>
        {{ __('social.all') }}
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
        {{ __('social.thread') }}
    </button>
</div>

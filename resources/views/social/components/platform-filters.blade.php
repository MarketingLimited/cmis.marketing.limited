{{-- Platform Filters Component --}}
<div class="flex flex-wrap gap-2 mb-4">
    <button @click="filterPlatform = 'all'"
            :class="filterPlatform === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/25' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            class="px-4 py-2 rounded-full font-medium transition-all duration-200 text-sm">
        <i class="fas fa-globe ms-1"></i>
        {{ __('social.all') }}
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

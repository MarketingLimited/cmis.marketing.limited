{{-- Composer Header/Tabs --}}
<div class="flex-shrink-0 px-6 py-3 border-b border-gray-200 bg-white">
    <div class="flex items-center gap-4">
        <button @click="composerTab = 'global'"
                :class="composerTab === 'global' ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                class="px-3 py-2 text-sm font-medium border-b-2 transition">
            <i class="fas fa-globe ms-1"></i>{{ __('publish.global_content') }}
        </button>
        <button type="button" class="text-gray-400 hover:text-blue-600 transition -me-2" title="{{ __('publish.platform_customization_help') }}">
            <i class="fas fa-info-circle text-xs"></i>
        </button>
        <template x-for="platform in getSelectedPlatforms()" :key="platform">
            <button @click="composerTab = platform"
                    :class="composerTab === platform ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                    class="px-3 py-2 text-sm font-medium border-b-2 transition">
                <i :class="getPlatformIcon(platform) + ' me-1'"></i>
                <span x-text="platform.charAt(0).toUpperCase() + platform.slice(1)"></span>
            </button>
        </template>
    </div>
</div>

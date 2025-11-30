{{-- Composer Header/Tabs --}}
<div class="flex-shrink-0 px-6 py-3 border-b border-gray-200 bg-white">
    <div class="flex items-center gap-2 overflow-x-auto">
        <button @click="composerTab = 'global'"
                :class="composerTab === 'global' ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                class="px-4 py-2.5 min-h-[44px] text-sm font-medium border-b-2 transition flex items-center whitespace-nowrap">
            <i class="fas fa-globe ms-1"></i>{{ __('publish.global_content') }}
        </button>
        <button type="button" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-blue-600 transition" title="{{ __('publish.platform_customization_help') }}">
            <i class="fas fa-info-circle"></i>
        </button>
        <template x-for="platform in getSelectedPlatforms()" :key="platform">
            <button @click="composerTab = platform"
                    :class="composerTab === platform ? 'text-blue-600 border-blue-600' : 'text-gray-500 border-transparent hover:text-gray-700'"
                    class="px-4 py-2.5 min-h-[44px] text-sm font-medium border-b-2 transition flex items-center whitespace-nowrap">
                <i :class="getPlatformIcon(platform) + ' me-1'"></i>
                <span x-text="getPlatformTabName(platform)"></span>
            </button>
        </template>
    </div>
</div>

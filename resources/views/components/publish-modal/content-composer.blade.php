{{-- Column 2: Content Composer --}}
<div class="flex-1 flex flex-col overflow-hidden">
    {{-- Composer Header/Tabs --}}
    @include('components.publish-modal.composer.tabs')

    {{-- Content Area --}}
    <div class="flex-1 overflow-y-auto p-6">
        {{-- Global Content Tab --}}
        <div x-show="composerTab === 'global'">
            @include('components.publish-modal.composer.global-content')
        </div>

        {{-- Per-Platform Content Tabs --}}
        <template x-for="platform in getSelectedPlatforms()" :key="platform">
            <div x-show="composerTab === platform">
                @include('components.publish-modal.composer.platform-content')
            </div>
        </template>
    </div>

    {{-- Scheduling Section --}}
    @include('components.publish-modal.composer.scheduling')
</div>

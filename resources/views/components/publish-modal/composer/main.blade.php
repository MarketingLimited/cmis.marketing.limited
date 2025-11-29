{{-- Column 2: Content Composer --}}
<div class="flex-1 flex flex-col min-h-0">
    {{-- Composer Header/Tabs --}}
    <div class="flex-shrink-0">
        @include('components.publish-modal.composer.tabs')
    </div>

    {{-- Content Area - Scrollable --}}
    <div class="flex-1 overflow-y-auto min-h-0 p-6">
        {{-- Global Content Tab --}}
        <div x-show="composerTab === 'global'">
            @include('components.publish-modal.composer.global-content')
        </div>

        {{-- Per-Platform Content Tabs --}}
        @include('components.publish-modal.composer.platform-content')
    </div>

    {{-- Scheduling Section - Fixed at bottom --}}
    <div class="flex-shrink-0">
        @include('components.publish-modal.composer.scheduling')
    </div>
</div>

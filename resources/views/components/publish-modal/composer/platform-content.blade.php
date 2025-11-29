{{-- Per-Platform Content Tabs --}}
<template x-for="platform in getSelectedPlatforms()" :key="platform">
    <div x-show="composerTab === platform">
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle ms-1"></i>
                {{ __('publish.customize_for_platform', ['platform' => '']) }}<span x-text="platform" class="font-semibold"></span>.
            </p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i :class="getPlatformIcon(platform) + ' ms-1'"></i>
                <span x-text="platform.charAt(0).toUpperCase() + platform.slice(1) + ' {{ __('publish.post_content') }}'"></span>
            </label>
            <textarea x-model="content.platforms[platform].text" rows="5"
                      :placeholder="'{{ __('publish.custom_content_for', ['platform' => '']) }}' + platform + '...'"
                      class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
        </div>

        {{-- Platform-Specific Options --}}
        @include('components.publish-modal.composer.platform-options.instagram')
        @include('components.publish-modal.composer.platform-options.twitter')
        @include('components.publish-modal.composer.platform-options.facebook')
        @include('components.publish-modal.composer.platform-options.tiktok')
        @include('components.publish-modal.composer.platform-options.youtube')
        @include('components.publish-modal.composer.platform-options.google-business')

        {{-- Location Tagging (All Platforms) --}}
        @include('components.publish-modal.composer.platform-options.location')
    </div>
</template>

{{--
    Publish Modal Component - Modular Structure
    Refactored into smaller components for maintainability.

    Directory Structure:
    - publish-modal/
        - header.blade.php (~33 lines)
        - warnings-banner.blade.php (~53 lines)
        - profile-selector.blade.php (~171 lines)
        - composer/
            - main.blade.php
            - tabs.blade.php (~22 lines)
            - global-content.blade.php (~218 lines)
            - platform-content.blade.php (~503 lines)
            - scheduling.blade.php (~101 lines)
        - preview-panel.blade.php (~341 lines)
        - footer.blade.php (~11 lines)
        - overlays/
            - hashtag-manager.blade.php (~141 lines)
            - mention-picker.blade.php (~59 lines)
            - calendar.blade.php (~94 lines)
            - best-times.blade.php (~50 lines)
            - media-source-picker.blade.php (~97 lines)
            - media-library.blade.php (~71 lines)

    JavaScript:
    - resources/js/components/publish-modal.js (~1736 lines)

    Total: ~75 lines (container) vs original 3730 lines
    All sub-components under 500 lines
--}}

{{-- Load Alpine.js Component --}}
<script src="{{ asset('js/components/publish-modal.js') }}"></script>

{{-- Modal Container --}}
<div x-data="publishModal()"
     x-init="$nextTick(() => { /* Ensure reactive system is ready */ })"
     x-show="open"
     x-cloak
     dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
     data-i18n-select-profile="{{ __('publish.select_at_least_one_profile') }}"
     data-i18n-content-required="{{ __('publish.content_or_media_required') }}"
     data-i18n-schedule-datetime-required="{{ __('publish.schedule_datetime_required') }}"
     data-i18n-schedule-must-be-future="{{ __('publish.schedule_must_be_future') }}"
     data-i18n-instagram-character-limit="{{ __('publish.instagram_character_limit') }}"
     data-i18n-instagram-reel-requires-video="{{ __('publish.instagram_reel_requires_video') }}"
     data-i18n-instagram-story-requires-media="{{ __('publish.instagram_story_requires_media') }}"
     data-i18n-instagram-max-media="{{ __('publish.instagram_max_media') }}"
     data-i18n-twitter-character-limit="{{ __('publish.twitter_character_limit') }}"
     data-i18n-twitter-max-images="{{ __('publish.twitter_max_images') }}"
     data-i18n-twitter-max-videos="{{ __('publish.twitter_max_videos') }}"
     data-i18n-twitter-no-mixed-media="{{ __('publish.twitter_no_mixed_media') }}"
     data-i18n-linkedin-character-limit="{{ __('publish.linkedin_character_limit') }}"
     data-i18n-linkedin-partner-required="{{ __('publish.linkedin_partner_required') }}"
     data-i18n-tiktok-character-limit="{{ __('publish.tiktok_character_limit') }}"
     data-i18n-tiktok-video-required="{{ __('publish.tiktok_video_required') }}"
     data-i18n-tiktok-mp4-h264-required="{{ __('publish.tiktok_mp4_h264_required') }}"
     data-i18n-youtube-title-required="{{ __('publish.youtube_title_required') }}"
     data-i18n-youtube-video-required="{{ __('publish.youtube_video_required') }}"
     data-i18n-snapchat-media-required="{{ __('publish.snapchat_media_required') }}"
     data-i18n-reset-all-confirm="{{ __('publish.reset_all_confirm') }}"
     data-i18n-reset-all-success="{{ __('publish.reset_all_success') }}"
     data-i18n-apply-to-all-confirm="{{ __('publish.apply_to_all_confirm') }}"
     data-i18n-applied-to-all-success="{{ __('publish.applied_to_all_success') }}"
     class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="closeModal()">

    {{-- Backdrop - Enhanced with stronger opacity for better focus --}}
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal()"></div>

    {{-- Modal Panel - Enhanced elevation with stronger shadow and border --}}
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             id="publish-modal-panel"
             class="relative w-full max-w-7xl max-h-[90vh] bg-white rounded-2xl shadow-2xl ring-1 ring-gray-900/10 flex flex-col"
             style="display: flex !important; flex-direction: column !important;">

            {{-- Modal Header --}}
            @include('components.publish-modal.header')

            {{-- Platform Warnings Banner --}}
            @include('components.publish-modal.warnings-banner')

            {{-- Main Content - Responsive Layout --}}
            {{-- Mobile: Single column (composer only, profile/preview as overlays) --}}
            {{-- Tablet: Two columns (profile selector + composer, preview as overlay) --}}
            {{-- Desktop: Three columns (all visible) --}}
            {{-- flex-1 allows this to grow, overflow-y-auto enables scrolling within --}}
            {{-- z-10 keeps content below footer (z-20) and overlays (z-50) --}}
            <div id="publish-modal-content" class="flex-1 flex flex-col lg:flex-row overflow-y-auto min-h-0 relative z-10">

                {{-- Column 1: Profile Selector (Hidden on mobile, overlay on tablet, sidebar on desktop) --}}
                <div class="hidden lg:flex lg:w-80 lg:flex-shrink-0 lg:border-e lg:border-gray-100">
                    @include('components.publish-modal.profile-selector')
                </div>

                {{-- Mobile Profile Selector Button (Only on mobile) --}}
                <div class="lg:hidden flex-shrink-0 px-6 py-3 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                    <button @click="showMobileProfileSelector = !showMobileProfileSelector"
                            class="w-full px-4 py-2.5 min-h-[44px] bg-white border-2 border-indigo-200 rounded-lg text-sm font-medium text-indigo-700 hover:bg-indigo-50 transition flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-users"></i>
                            <span x-text="selectedProfiles.length > 0 ? '{{ __('publish.selected_accounts') }}: ' + selectedProfiles.length : '{{ __('publish.select_accounts') }}'"></span>
                        </span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>

                {{-- Column 2: Content Composer (Always visible) --}}
                @include('components.publish-modal.composer.main')

                {{-- Column 3: Preview Panel (Hidden on mobile/tablet, sidebar on desktop) --}}
                {{-- x-init repositions preview panel inside content area, fixing DOM nesting issues --}}
                <div id="publish-modal-preview"
                     x-init="$nextTick(() => {
                         const preview = document.getElementById('publish-modal-preview');
                         const content = document.getElementById('publish-modal-content');
                         if (preview && content && preview.parentElement !== content) {
                             content.appendChild(preview);
                         }
                     })"
                     class="hidden xl:flex xl:w-96 xl:flex-shrink-0 xl:border-s xl:border-gray-100">
                    @include('components.publish-modal.preview-panel')
                </div>

                {{-- Mobile Preview Button (Only on mobile/tablet) --}}
                <div id="publish-modal-mobile-preview-btn"
                     x-init="$nextTick(() => {
                         const btn = document.getElementById('publish-modal-mobile-preview-btn');
                         const content = document.getElementById('publish-modal-content');
                         if (btn && content && btn.parentElement !== content) {
                             content.appendChild(btn);
                         }
                     })"
                     class="xl:hidden flex-shrink-0 px-6 py-3 border-t border-gray-200 bg-gray-50">
                    <button @click="showMobilePreview = !showMobilePreview"
                            class="w-full px-4 py-2.5 min-h-[44px] bg-blue-600 rounded-lg text-sm font-medium text-white hover:bg-blue-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-eye"></i>
                        <span>{{ __('publish.preview') }}</span>
                    </button>
                </div>

            </div>

            {{-- Mobile Profile Selector Overlay --}}
            <div x-show="showMobileProfileSelector"
                 x-cloak
                 @click.self="showMobileProfileSelector = false"
                 class="lg:hidden fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-end"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div x-show="showMobileProfileSelector"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="translate-y-full"
                     x-transition:enter-end="translate-y-0"
                     class="w-full max-h-[80vh] bg-white rounded-t-2xl overflow-hidden flex flex-col">
                    {{-- Mobile Profile Selector Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                        <h3 class="text-lg font-bold text-gray-900">{{ __('publish.select_accounts') }}</h3>
                        <button @click="showMobileProfileSelector = false" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-lg hover:bg-white/50">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    {{-- Profile Selector Content --}}
                    <div class="flex-1 overflow-hidden">
                        @include('components.publish-modal.profile-selector')
                    </div>
                    {{-- Done Button --}}
                    <div class="px-6 py-4 border-t border-gray-200 bg-white">
                        <button @click="showMobileProfileSelector = false" class="w-full px-4 py-2.5 min-h-[44px] bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition">
                            {{ __('publish.done') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile Preview Overlay --}}
            <div x-show="showMobilePreview"
                 x-cloak
                 @click.self="showMobilePreview = false"
                 class="xl:hidden fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center p-4"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100">
                <div x-show="showMobilePreview"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="w-full max-w-lg max-h-[90vh] bg-white rounded-2xl overflow-hidden flex flex-col">
                    {{-- Mobile Preview Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                        <h3 class="text-lg font-bold text-gray-900">{{ __('publish.preview') }}</h3>
                        <button @click="showMobilePreview = false" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 rounded-lg hover:bg-white/50">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    {{-- Preview Content --}}
                    <div class="flex-1 overflow-hidden">
                        @include('components.publish-modal.preview-panel')
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            @include('components.publish-modal.footer')

            {{-- Overlay Modals - Inside modal panel for proper Alpine scope access --}}
            @include('components.publish-modal.overlays.hashtag-manager')
            @include('components.publish-modal.overlays.mention-picker')
            @include('components.publish-modal.overlays.calendar')
            @include('components.publish-modal.overlays.best-times')
            @include('components.publish-modal.overlays.media-source-picker')
            @include('components.publish-modal.overlays.media-library')
            @include('components.publish-modal.overlays.ai-assistant')
        </div>
    </div>
</div>

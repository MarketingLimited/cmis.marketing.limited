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

{{-- Load Alpine.js Component - must be before x-data div --}}
<script src="{{ asset('js/components/publish-modal.js') }}"></script>
<script>
    // Ensure function is globally available
    if (typeof window.publishModal === 'undefined') {
        window.publishModal = publishModal;
    }
</script>

{{-- Modal Container --}}
<div x-data="publishModal()" x-show="open" x-cloak dir="rtl"
     class="fixed inset-0 z-50 overflow-hidden" @keydown.escape.window="closeModal()">

    {{-- Backdrop --}}
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900 bg-opacity-75" @click="closeModal()"></div>

    {{-- Modal Panel --}}
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div x-show="open" x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             class="relative w-full max-w-7xl max-h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">

            {{-- Modal Header --}}
            @include('components.publish-modal.header')

            {{-- Platform Warnings Banner --}}
            @include('components.publish-modal.warnings-banner')

            {{-- Main Content - 3 Columns --}}
            <div class="flex-1 flex overflow-hidden">

                {{-- Column 1: Profile Selector --}}
                @include('components.publish-modal.profile-selector')

                {{-- Column 2: Content Composer --}}
                @include('components.publish-modal.composer.main')

                {{-- Column 3: Preview Panel --}}
                @include('components.publish-modal.preview-panel')

            </div>

            {{-- Footer --}}
            @include('components.publish-modal.footer')
        </div>
    </div>

    {{-- Overlay Modals --}}
    @include('components.publish-modal.overlays.hashtag-manager')
    @include('components.publish-modal.overlays.mention-picker')
    @include('components.publish-modal.overlays.calendar')
    @include('components.publish-modal.overlays.best-times')
    @include('components.publish-modal.overlays.media-source-picker')
    @include('components.publish-modal.overlays.media-library')
</div>

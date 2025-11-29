{{--
    Language Switcher Component

    A dropdown component for switching between Arabic and English locales

    Usage:
    <x-language-switcher />

    Features:
    - Shows current language flag/name
    - Dropdown with both language options
    - Highlights active language
    - Preserves current page after switching
--}}
@php
    $currentLocale = app()->getLocale();
    $isArabic = $currentLocale === 'ar';
@endphp

<div x-data="{
    open: false,
    switching: false,
    init() {
        console.log('[LANGUAGE SWITCHER] ========== DEBUG START ==========');
        console.log('[LANGUAGE SWITCHER] Current locale:', '{{ $currentLocale }}');
        console.log('[LANGUAGE SWITCHER] All cookies:', document.cookie);
        console.log('[LANGUAGE SWITCHER] app_locale cookie:', this.getCookie('app_locale'));
        console.log('[LANGUAGE SWITCHER] ========== DEBUG END ==========');
    },
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    },
    afterSwitch() {
        console.log('[LANGUAGE SWITCHER] ========== AFTER SWITCH ==========');
        console.log('[LANGUAGE SWITCHER] All cookies after switch:', document.cookie);
        console.log('[LANGUAGE SWITCHER] app_locale cookie after switch:', this.getCookie('app_locale'));
        console.log('[LANGUAGE SWITCHER] ========================================');
    }
}" @click.away="open = false" class="relative">
    {{-- Trigger Button --}}
    <button
        @click="console.log('[LANGUAGE SWITCHER] Dropdown toggled, open:', !open); open = !open"
        type="button"
        data-testid="language-switcher"
        id="language-switcher-btn"
        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ $isArabic ? 'Switch language - العربية' : 'Switch language - English' }}"
        :disabled="switching"
        :class="{ 'opacity-50 cursor-wait': switching }"
    >
        {{-- Language Icon/Flag or Loading Spinner --}}
        <svg x-show="!switching" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <svg x-show="switching" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        {{-- Current Language Display --}}
        <span class="hidden sm:inline">
            <span x-show="!switching">{{ $isArabic ? 'العربية' : 'English' }}</span>
            <span x-show="switching">{{ __('common.switching') }}...</span>
        </span>

        {{-- Dropdown Arrow --}}
        <svg
            class="w-4 h-4 transition-transform duration-200"
            :class="{ 'rotate-180': open }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute {{ $isArabic ? 'left-0' : 'right-0' }} mt-2 w-48 origin-top-{{ $isArabic ? 'left' : 'right' }} rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 dark:divide-gray-700 focus:outline-none z-50"
        role="menu"
        aria-orientation="vertical"
        style="display: none;"
        x-cloak
    >
        <div class="py-1">
            {{-- Arabic Option --}}
            <form method="POST" action="{{ route('language.switch', 'ar') }}" class="block" @submit="console.log('[LANGUAGE SWITCHER] 🔄 FORM SUBMITTED - Switching to Arabic...'); console.log('[LANGUAGE SWITCHER] Form action:', $event.target.action); console.log('[LANGUAGE SWITCHER] Cookies BEFORE submit:', document.cookie); switching = true; open = false">
                @csrf
                <button
                    type="submit"
                    class="group flex items-center w-full px-4 py-3 text-sm {{ $isArabic ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors duration-150"
                    role="menuitem"
                    :disabled="switching"
                >
                    {{-- Arabic Flag Emoji or Icon --}}
                    <span class="text-2xl me-3">🇸🇦</span>

                    <div class="flex-1 text-{{ $isArabic ? 'start' : 'start' }}">
                        <p class="font-medium">العربية</p>
                        <p class="text-xs {{ $isArabic ? 'text-indigo-500' : 'text-gray-500 dark:text-gray-400' }}">Arabic</p>
                    </div>

                    {{-- Active Indicator --}}
                    @if($isArabic)
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    @endif
                </button>
            </form>

            {{-- English Option --}}
            <form method="POST" action="{{ route('language.switch', 'en') }}" class="block" @submit="console.log('[LANGUAGE SWITCHER] 🔄 FORM SUBMITTED - Switching to English...'); console.log('[LANGUAGE SWITCHER] Form action:', $event.target.action); console.log('[LANGUAGE SWITCHER] Cookies BEFORE submit:', document.cookie); switching = true; open = false">
                @csrf
                <button
                    type="submit"
                    class="group flex items-center w-full px-4 py-3 text-sm {{ !$isArabic ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700' }} transition-colors duration-150"
                    role="menuitem"
                    :disabled="switching"
                >
                    {{-- English Flag Emoji or Icon --}}
                    <span class="text-2xl me-3">🇬🇧</span>

                    <div class="flex-1 text-{{ $isArabic ? 'start' : 'start' }}">
                        <p class="font-medium">English</p>
                        <p class="text-xs {{ !$isArabic ? 'text-indigo-500' : 'text-gray-500 dark:text-gray-400' }}">الإنجليزية</p>
                    </div>

                    {{-- Active Indicator --}}
                    @if(!$isArabic)
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    @endif
                </button>
            </form>
        </div>
    </div>
</div>

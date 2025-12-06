@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50" x-data="{ mobileOpen: false }">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="{{ route('marketing.home') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">C</span>
                </div>
                <span class="text-xl font-bold text-slate-900 dark:text-white">CMIS</span>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('marketing.features') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                    {{ __('marketing.nav.features') }}
                </a>
                <a href="{{ route('marketing.pricing') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                    {{ __('marketing.nav.pricing') }}
                </a>
                <a href="{{ route('marketing.blog.index') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                    {{ __('marketing.nav.blog') }}
                </a>
                <a href="{{ route('marketing.about') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                    {{ __('marketing.nav.about') }}
                </a>
                <a href="{{ route('marketing.contact') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 dark:hover:text-red-400 transition">
                    {{ __('marketing.nav.contact') }}
                </a>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
                <!-- Language Switcher -->
                <a href="{{ route('locale.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
                   class="text-sm text-slate-600 dark:text-slate-300 hover:text-red-600 transition">
                    {{ app()->getLocale() === 'ar' ? 'EN' : 'العربية' }}
                </a>

                <!-- CTA Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{ route('login') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 transition">
                        {{ __('marketing.nav.login') }}
                    </a>
                    <a href="{{ route('marketing.demo') }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        {{ __('marketing.nav.demo') }}
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-600 dark:text-slate-300">
                    <i class="fas" :class="mobileOpen ? 'fa-times' : 'fa-bars'"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileOpen" x-collapse class="md:hidden pb-4">
            <div class="flex flex-col gap-3">
                <a href="{{ route('marketing.features') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.features') }}</a>
                <a href="{{ route('marketing.pricing') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.pricing') }}</a>
                <a href="{{ route('marketing.blog.index') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.blog') }}</a>
                <a href="{{ route('marketing.about') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.about') }}</a>
                <a href="{{ route('marketing.contact') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.contact') }}</a>
                <hr class="border-slate-200 dark:border-slate-700 my-2">
                <a href="{{ route('login') }}" class="text-slate-600 dark:text-slate-300 hover:text-red-600 py-2">{{ __('marketing.nav.login') }}</a>
                <a href="{{ route('marketing.demo') }}" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-center">{{ __('marketing.nav.demo') }}</a>
            </div>
        </div>
    </nav>
</header>

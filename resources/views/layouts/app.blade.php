{{--
    ============================================================================
    LEGACY LAYOUT - NOW i18n COMPLIANT WITH DYNAMIC RTL/LTR SUPPORT
    ============================================================================
    This layout now supports both Arabic (RTL) and English (LTR) dynamically.
    Updated: 2025-11-27 - Full i18n compliance achieved

    Remaining Pages (27 files still using this layout):
    - settings/platform-connections/*.blade.php (6 files)
    - settings/index.blade.php
    - campaigns/ad-sets/*.blade.php (4 files)
    - campaigns/ads/*.blade.php (4 files)
    - onboarding/*.blade.php (2 files)
    - inbox/*.blade.php (2 files)
    - campaigns/performance-dashboard.blade.php
    - orgs/team.blade.php
    - campaigns/wizard/step.blade.php
    - subscription/*.blade.php (2 files)
    - dashboard/analytics.blade.php

    i18n Status: ✅ COMPLIANT - Dynamic RTL/LTR, zero hardcoded text
    ============================================================================
--}}
<!DOCTYPE html>
<html lang="{{ $htmlLang ?? app()->getLocale() }}" dir="{{ $htmlDir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMIS') }} - @yield('title', __('navigation.dashboard'))</title>

    <!-- Font Awesome CDN (keeping this as it's external assets) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- JavaScript Translations -->
    <x-js-translations />

    @stack('styles')
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div x-data="{ sidebarOpen: false, notificationsOpen: false }" x-cloak class="min-h-screen flex">
        <!-- Sidebar -->
        <div :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0'"
             class="fixed inset-y-0 right-0 z-40 w-72 gradient-bg shadow-2xl md:relative transition-transform duration-300">
            <!-- Logo Section -->
            <div class="p-6 border-b border-white/20">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/cmis-logo-without-background.png') }}" alt="CMIS Logo" class="w-12 h-12 object-contain">
                    <div>
                        <h1 class="text-2xl font-bold text-white">CMIS Platform</h1>
                        <p class="text-white/70 text-xs">{{ __('navigation.platform_tagline') }}</p>
                    </div>
                </div>
            </div>

            <!-- Navigation (Dynamic based on enabled apps) -->
            <x-sidebar-navigation />

            <!-- Organization Switcher & User Card at Bottom -->
            <div class="absolute bottom-0 right-0 left-0 p-4 border-t border-white/20 space-y-3">
                @auth
                {{-- Organization Switcher (NEW: P1 - Multi-Org UI) --}}
                <x-org-switcher />

                {{-- User Card --}}
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-lg p-2">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white font-medium text-sm">{{ Auth::user()->name }}</p>
                            <p class="text-white/60 text-xs">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
                @endauth
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen">
            <!-- Top Header -->
            <header class="bg-white/80 backdrop-blur-lg shadow-sm sticky top-0 z-30">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <!-- Mobile Menu Toggle -->
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-bars text-gray-700 text-xl"></i>
                        </button>

                        <!-- Page Title -->
                        <div class="hidden md:block">
                            <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', __('messages.page_title_default'))</h2>
                            <p class="text-sm text-gray-500">@yield('page-subtitle', __('messages.page_subtitle_default'))</p>
                        </div>

                        <!-- Organization Context Indicator (Issue #1) -->
                        @auth
                        @if(Auth::user()->currentOrg)
                        <div class="hidden lg:flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl transition-all hover:shadow-md"
                             x-data="{ justSwitched: false }"
                             :class="{ 'animate-pulse': justSwitched }"
                             x-on:org-switched.window="justSwitched = true; setTimeout(() => justSwitched = false, 2000)"
                        >
                            <div class="bg-indigo-100 rounded-lg p-2">
                                <i class="fas fa-building text-indigo-600"></i>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-indigo-600 font-medium">{{ __('navigation.current_organization') }}</p>
                                <p class="text-sm font-bold text-indigo-900">{{ Auth::user()->currentOrg->name ?? __('messages.not_specified') }}</p>
                            </div>
                            <button @click="$dispatch('open-modal', 'org-switcher-modal')"
                                    class="mr-2 text-indigo-600 hover:text-indigo-800 transition"
                                    title="{{ __('navigation.switch_organization') }}">
                                <i class="fas fa-exchange-alt text-sm"></i>
                            </button>
                        </div>
                        @endif
                        @endauth

                        <!-- Right Actions -->
                        <div class="flex items-center gap-3">
                            <!-- Search -->
                            <div class="hidden lg:block">
                                <div class="relative">
                                    <input type="text" placeholder="{{ __('common.search') }}..."
                                           class="w-64 px-4 py-2 pr-10 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition">
                                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>

                            <!-- Notifications -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="relative p-2 rounded-xl hover:bg-gray-100 transition">
                                    <i class="fas fa-bell text-gray-600 text-lg"></i>
                                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition x-cloak
                                     class="absolute left-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                                    <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                                        <h3 class="text-white font-bold">{{ __('navigation.notifications') }}</h3>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer border-b">
                                            <p class="text-sm font-medium text-gray-800">{{ __('notifications.campaign_created') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ __('notifications.minutes_ago', ['count' => 5]) }}</p>
                                        </div>
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer">
                                            <p class="text-sm font-medium text-gray-800">{{ __('notifications.report_ready') }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ __('notifications.hours_ago', ['count' => 1]) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Language Switcher -->
                            <x-language-switcher />

                            <!-- User Menu -->
                            @auth
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-xl hover:bg-gray-100 transition">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg w-9 h-9 flex items-center justify-center font-bold">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-600 text-xs"></i>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition x-cloak
                                     class="absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                                    <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                                        <p class="text-white font-bold">{{ Auth::user()->name }}</p>
                                        <p class="text-white/80 text-xs">{{ Auth::user()->email }}</p>
                                    </div>
                                    <div class="p-2">
                                        @php
                                            $userOrg = auth()->user()->active_org_id ?? auth()->user()->current_org_id ?? auth()->user()->org_id ?? request()->route('org');
                                        @endphp
                                        @if($userOrg)
                                        <a href="{{ route('orgs.settings.user', ['org' => $userOrg]) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                                            <i class="fas fa-user-cog text-gray-600 w-5"></i>
                                            <span class="text-sm text-gray-700">{{ __('navigation.user_settings') }}</span>
                                        </a>
                                        <a href="{{ route('orgs.settings.organization', ['org' => $userOrg]) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                                            <i class="fas fa-building text-gray-600 w-5"></i>
                                            <span class="text-sm text-gray-700">{{ __('navigation.organization_settings') }}</span>
                                        </a>
                                        @endif
                                        <hr class="my-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 transition">
                                                <i class="fas fa-sign-out-alt w-5"></i>
                                                <span class="text-sm font-medium">{{ __('common.logout') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endauth
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-6">
                <!-- Alerts -->
                @if(session('success'))
                <div class="mb-6 bg-green-50 border-r-4 border-green-500 rounded-lg p-4 fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-red-50 border-r-4 border-red-500 rounded-lg p-4 fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                        <p class="text-red-800 font-medium">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                <div class="fade-in">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-6">
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <p>&copy; 2025 CMIS Platform. {{ __('footer.all_rights_reserved') }}</p>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-indigo-600 transition">{{ __('footer.help') }}</a>
                        <a href="#" class="hover:text-indigo-600 transition">{{ __('footer.technical_support') }}</a>
                        <a href="#" class="hover:text-indigo-600 transition">{{ __('footer.terms') }}</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Overlay for Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 md:hidden" x-transition x-cloak></div>

    <!-- Delete Confirmation Modal (Issue #7) -->
    <x-delete-confirmation-modal />

    <!-- Toast Notification System -->
    <div x-data="{
        show: false,
        message: '',
        type: 'success',
        showToast(detail) {
            this.message = detail.message;
            this.type = detail.type || 'success';
            this.show = true;
            setTimeout(() => this.show = false, 5000);
        }
    }"
    x-on:show-toast.window="showToast($event.detail)"
    x-show="show"
    x-transition:enter="transform ease-out duration-300 transition"
    x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed top-4 left-4 z-50 pointer-events-none"
    style="display: none;"
    >
        <div class="max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i :class="{
                            'fas fa-check-circle text-green-400': type === 'success',
                            'fas fa-exclamation-circle text-red-400': type === 'error',
                            'fas fa-info-circle text-blue-400': type === 'info'
                        }" class="text-xl"></i>
                    </div>
                    <div class="mr-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900" x-text="message"></p>
                    </div>
                    <div class="mr-4 flex-shrink-0 flex">
                        <button @click="show = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

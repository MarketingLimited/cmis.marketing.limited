@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $dir = $htmlDir ?? ($isRtl ? 'rtl' : 'ltr');
    $lang = $htmlLang ?? ($locale === 'ar' ? 'ar' : 'en');
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}" x-data="superAdminLayout()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('super_admin.nav.dashboard')) - {{ __('super_admin.title') }}</title>

    <!-- Tailwind CSS CDN -->
    <script>
        (function() {
            const originalWarn = console.warn;
            console.warn = function(...args) {
                if (args[0] && typeof args[0] === 'string' && args[0].includes('cdn.tailwindcss.com')) {
                    return;
                }
                originalWarn.apply(console, args);
            };
        })();
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        'super-admin': {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        }
                    }
                }
            }
        };
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    @if(request()->routeIs('super-admin.dashboard') || request()->routeIs('super-admin.analytics.*'))
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endif

    <!-- JavaScript Translations -->
    <x-js-translations />

    <style>
        [x-cloak] { display: none !important; }

        .gradient-super-admin {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        }

        .gradient-dark {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.3);
            border-radius: 4px;
        }

        @media (max-width: 640px) {
            button, a {
                touch-action: manipulation;
            }
            button, a, input, select, textarea {
                min-height: 44px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans antialiased">

    <!-- Impersonation Banner -->
    @if(session('impersonating'))
    <div class="bg-yellow-500 text-yellow-900 px-4 py-2 text-center text-sm font-medium">
        <i class="fas fa-user-secret {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
        {{ __('super_admin.impersonating_user', ['name' => session('impersonated_user_name', 'User')]) }}
        <form action="{{ route('super-admin.stop-impersonating') }}" method="POST" class="inline {{ $isRtl ? 'mr-4' : 'ml-4' }}">
            @csrf
            <button type="submit" class="underline hover:no-underline font-bold">
                {{ __('super_admin.stop_impersonating') }}
            </button>
        </form>
    </div>
    @endif

    <!-- Main Container -->
    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
             x-cloak></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}'"
               class="fixed inset-y-0 {{ $isRtl ? 'right-0' : 'left-0' }} z-40 w-64 gradient-dark shadow-2xl transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

            <!-- Logo Section -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-700/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white">{{ __('super_admin.title') }}</h1>
                        <p class="text-[10px] text-slate-400">{{ __('super_admin.subtitle') }}</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-2 rounded-lg transition-all">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="px-3 py-4 overflow-y-auto h-[calc(100vh-10rem)] custom-scrollbar">
                <!-- Dashboard -->
                <a href="{{ route('super-admin.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.dashboard') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.dashboard') }}</span>
                </a>

                <!-- Management Section -->
                <div class="mt-6 mb-2">
                    <h3 class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('super_admin.nav.management') }}</h3>
                </div>

                <a href="{{ route('super-admin.orgs.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.orgs.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-building w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.organizations') }}</span>
                </a>

                <a href="{{ route('super-admin.users.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.users.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.users') }}</span>
                </a>

                <!-- Billing Section -->
                <div class="mt-6 mb-2">
                    <h3 class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('super_admin.nav.billing') }}</h3>
                </div>

                <a href="{{ route('super-admin.plans.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.plans.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-tags w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.plans') }}</span>
                </a>

                <a href="{{ route('super-admin.subscriptions.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.subscriptions.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-credit-card w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.subscriptions') }}</span>
                </a>

                <!-- Platform Section -->
                <div class="mt-6 mb-2">
                    <h3 class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('super_admin.nav.platform') ?? 'Platform' }}</h3>
                </div>

                <a href="{{ route('super-admin.apps.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.apps.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-puzzle-piece w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.apps') ?? 'Apps' }}</span>
                </a>

                <a href="{{ route('super-admin.integrations.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.integrations.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-plug w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.integrations') ?? 'Integrations' }}</span>
                </a>

                <!-- Monitoring Section -->
                <div class="mt-6 mb-2">
                    <h3 class="px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('super_admin.nav.monitoring') }}</h3>
                </div>

                <a href="{{ route('super-admin.analytics.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.analytics.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-chart-line w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.analytics') }}</span>
                </a>

                <a href="{{ route('super-admin.system.health') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-lg mb-1 transition-all {{ request()->routeIs('super-admin.system.*') ? 'bg-red-600 text-white' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' }}">
                    <i class="fas fa-heartbeat w-5 text-center"></i>
                    <span>{{ __('super_admin.nav.system_health') }}</span>
                </a>
            </nav>

            <!-- User Section -->
            <div class="absolute bottom-0 left-0 right-0 p-3 border-t border-slate-700/50 bg-slate-900/50">
                <div class="flex items-center gap-3 p-2">
                    <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ substr(auth()->user()->name ?? 'SA', 0, 2) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? __('super_admin.title') }}</p>
                        <p class="text-xs text-slate-400">{{ __('super_admin.role') }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all" title="{{ __('common.logout') }}">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Navigation Bar -->
            <header class="flex items-center justify-between h-16 px-4 sm:px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 text-gray-600 dark:text-gray-300 lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <!-- Breadcrumb -->
                    <nav class="hidden sm:flex items-center gap-2 text-sm">
                        <a href="{{ route('super-admin.dashboard') }}" class="text-gray-500 hover:text-red-600 transition">
                            <i class="fas fa-home"></i>
                        </a>
                        @yield('breadcrumb')
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Quick Actions -->
                    <a href="/" class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        <i class="fas fa-arrow-{{ $isRtl ?? false ? 'right' : 'left' }}"></i>
                        <span>{{ __('super_admin.back_to_app') }}</span>
                    </a>

                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode()" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <!-- Language Switcher -->
                    <x-language-switcher />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 dark:bg-gray-900 p-4 sm:p-6">
                <!-- Flash Messages -->
                @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.isRtl = {{ $isRtl ? 'true' : 'false' }};

        function superAdminLayout() {
            return {
                sidebarOpen: false,
                darkMode: false,

                init() {
                    this.sidebarOpen = window.innerWidth >= 1024;
                    window.addEventListener('resize', () => {
                        this.sidebarOpen = window.innerWidth >= 1024;
                    });

                    const savedDarkMode = localStorage.getItem('superAdminDarkMode');
                    if (savedDarkMode !== null) {
                        this.darkMode = savedDarkMode === 'true';
                    }
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('superAdminDarkMode', this.darkMode);
                }
            };
        }
    </script>

    @stack('scripts')
</body>
</html>

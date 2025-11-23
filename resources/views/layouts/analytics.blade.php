<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMIS') }} - @yield('title', 'Analytics Dashboard')</title>

    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js (v3.x) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js (v4.x) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Additional Styles -->
    @stack('styles')

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Gradient Background */
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Smooth Transitions */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div x-data="{ sidebarOpen: true, notificationsOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <div :class="sidebarOpen ? 'w-64' : 'w-20'"
             class="gradient-bg shadow-2xl transition-all duration-300 flex flex-col">
            <!-- Logo Section -->
            <div class="p-6 border-b border-white/20">
                <div class="flex items-center" :class="sidebarOpen ? 'gap-3' : 'justify-center'">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <div x-show="sidebarOpen" x-transition>
                        <h1 class="text-xl font-bold text-white">Analytics Hub</h1>
                        <p class="text-white/70 text-xs">Enterprise Dashboard</p>
                    </div>
                </div>
                <button @click="sidebarOpen = !sidebarOpen"
                        class="mt-4 w-full bg-white/10 hover:bg-white/20 text-white rounded-lg py-2 transition">
                    <i :class="sidebarOpen ? 'fa-chevron-left' : 'fa-chevron-right'" class="fas"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4 space-y-2 flex-1 overflow-y-auto">
                <a href="{{ route('analytics.enterprise') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('analytics.enterprise') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }} transition">
                    <i class="fas fa-th-large text-lg w-6"></i>
                    <span x-show="sidebarOpen" x-transition class="font-medium">Enterprise Dashboard</span>
                </a>

                <a href="{{ route('analytics.realtime') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('analytics.realtime') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }} transition">
                    <i class="fas fa-bolt text-lg w-6"></i>
                    <span x-show="sidebarOpen" x-transition class="font-medium">Real-Time</span>
                </a>

                <a href="{{ route('analytics.campaigns') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('analytics.campaigns') || request()->routeIs('analytics.campaign') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }} transition">
                    <i class="fas fa-bullhorn text-lg w-6"></i>
                    <span x-show="sidebarOpen" x-transition class="font-medium">Campaigns</span>
                </a>

                <a href="{{ route('analytics.kpis') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('analytics.kpis') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }} transition">
                    <i class="fas fa-tachometer-alt text-lg w-6"></i>
                    <span x-show="sidebarOpen" x-transition class="font-medium">KPIs</span>
                </a>

                <div class="pt-4 border-t border-white/20 mt-4">
                    <p x-show="sidebarOpen" x-transition class="text-white/50 text-xs font-medium px-4 mb-2">Main App</p>

                    <a href="{{ route('dashboard.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition">
                        <i class="fas fa-home text-lg w-6"></i>
                        <span x-show="sidebarOpen" x-transition class="font-medium">Dashboard</span>
                    </a>

                    <a href="{{ route('campaigns.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition">
                        <i class="fas fa-folder text-lg w-6"></i>
                        <span x-show="sidebarOpen" x-transition class="font-medium">Campaigns</span>
                    </a>

                    <a href="{{ route('settings.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-white/80 hover:bg-white/10 transition">
                        <i class="fas fa-cog text-lg w-6"></i>
                        <span x-show="sidebarOpen" x-transition class="font-medium">Settings</span>
                    </a>
                </div>
            </nav>

            <!-- User Card at Bottom -->
            <div class="p-4 border-t border-white/20">
                @auth
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3">
                    <div class="flex items-center" :class="sidebarOpen ? 'gap-3' : 'justify-center'">
                        <div class="bg-white/20 rounded-lg p-2">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div x-show="sidebarOpen" x-transition class="flex-1">
                            <p class="text-white font-medium text-sm truncate">{{ Auth::user()->name }}</p>
                            <p class="text-white/60 text-xs truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
                @endauth
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white shadow-sm sticky top-0 z-30">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <!-- Page Title -->
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'Analytics Dashboard')</h2>
                            <p class="text-sm text-gray-500">@yield('page-subtitle', 'Real-time insights and performance metrics')</p>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center gap-3">
                            <!-- Org Switcher -->
                            @auth
                            <x-org-switcher />
                            @endauth

                            <!-- Time Range Selector (Optional) -->
                            @stack('header-actions')

                            <!-- Refresh Button -->
                            <button onclick="location.reload()"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center gap-2">
                                <i class="fas fa-sync-alt"></i>
                                <span class="hidden sm:inline">Refresh</span>
                            </button>

                            <!-- User Menu -->
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg w-9 h-9 flex items-center justify-center font-bold">
                                        {{ Auth::check() ? substr(Auth::user()->name, 0, 1) : 'G' }}
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-600 text-xs"></i>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition x-cloak
                                     class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden">
                                    <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                                        <p class="text-white font-bold">{{ Auth::user()->name ?? 'Guest' }}</p>
                                        <p class="text-white/80 text-xs">{{ Auth::user()->email ?? '' }}</p>
                                    </div>
                                    <div class="p-2">
                                        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                                            <i class="fas fa-cog text-gray-600 w-5"></i>
                                            <span class="text-sm text-gray-700">Settings</span>
                                        </a>
                                        <hr class="my-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 transition">
                                                <i class="fas fa-sign-out-alt w-5"></i>
                                                <span class="text-sm font-medium">Logout</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
                <!-- Alerts -->
                @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-lg p-4 fade-in">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                        <p class="text-green-800 font-medium">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-lg p-4 fade-in">
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
            <footer class="bg-white border-t border-gray-200 py-3 px-6">
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <p>&copy; 2025 CMIS Analytics. All rights reserved.</p>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-indigo-600 transition">Help</a>
                        <a href="#" class="hover:text-indigo-600 transition">Documentation</a>
                        <a href="#" class="hover:text-indigo-600 transition">API</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Alpine.js Components -->
    <script type="module">
        // Import Phase 8 components
        import {
            realtimeDashboard,
            campaignAnalytics,
            kpiDashboard,
            notificationCenter
        } from '/resources/js/components/index.js';

        // Register with Alpine (auto-registration happens in index.js)
        // This is a fallback if auto-registration fails
        if (window.Alpine) {
            window.Alpine.data('realtimeDashboard', realtimeDashboard);
            window.Alpine.data('campaignAnalytics', campaignAnalytics);
            window.Alpine.data('kpiDashboard', kpiDashboard);
            window.Alpine.data('notificationCenter', notificationCenter);
        }

        // Store auth token in localStorage for components
        @auth
        localStorage.setItem('auth_token', '{{ Auth::user()->currentAccessToken()?->plainTextToken ?? session('api_token') ?? '' }}');
        localStorage.setItem('user_id', '{{ Auth::user()->user_id }}');
        @endauth
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="appLayout()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta-description', 'نظام CMIS للتسويق الذكي - إدارة الحملات والمؤسسات')">
    <meta name="theme-color" content="#667eea">

    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="@yield('title', 'CMIS') - لوحة التحكم">
    <meta property="og:description" content="@yield('meta-description', 'نظام CMIS للتسويق الذكي')">
    <meta property="og:type" content="website">

    <title>@yield('title', 'CMIS') - لوحة التحكم</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN - loaded early with defer to prevent FOUC -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Icons: Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js CDN - Conditional Loading -->
    @if(request()->routeIs('analytics.*') || request()->routeIs('orgs.dashboard.*') || request()->routeIs('dashboard.*'))
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @endif

    <style>
        [x-cloak] { display: none !important; }

        /* RTL specific fixes */
        .rtl-flip {
            transform: scaleX(-1);
        }

        /* Custom scrollbar */
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

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Gradient backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .gradient-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            /* Prevent double-tap zoom on buttons */
            button, a {
                touch-action: manipulation;
            }

            /* Larger tap targets on mobile */
            button, a, input, select, textarea {
                min-height: 44px;
            }
        }

        /* Enhanced focus indicators for accessibility */
        button:focus-visible,
        a:focus-visible,
        input:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Reduce motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* Performance optimizations */
        .org-card {
            contain: layout style paint;
        }

        /* Better image rendering */
        img {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }

        /* Prevent layout shift */
        [x-cloak] {
            display: none !important;
        }

        /* Optimize animations */
        @media (prefers-reduced-motion: no-preference) {
            .animate-pulse {
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* User Menu Bottom Sheet Enhancements */
        @media (max-width: 767px) {
            /* Prevent body scroll when menu is open */
            body:has([x-data*="userMenuOpen"]) {
                overflow: hidden;
            }

            /* Smooth slide-up animation */
            [x-show="userMenuOpen"] {
                will-change: transform;
            }

            /* Better touch targets for mobile */
            [x-data*="userMenuOpen"] a,
            [x-data*="userMenuOpen"] button {
                min-height: 56px;
                touch-action: manipulation;
            }
        }

        /* Enhanced z-index management */
        .z-\[60\] {
            z-index: 60;
        }

        .z-\[70\] {
            z-index: 70;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased" :class="{ 'dark:bg-gray-900': darkMode }">

    <!-- Notification Toast Container -->
    <div x-data="notificationManager()"
         @notify.window="addNotification($event.detail)"
         class="fixed top-4 left-4 z-50 space-y-2"
         x-cloak>
        <template x-for="notification in notifications" :key="notification.id">
            <div x-show="notification.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 :class="{
                     'bg-green-500': notification.type === 'success',
                     'bg-blue-500': notification.type === 'info',
                     'bg-yellow-500': notification.type === 'warning',
                     'bg-red-500': notification.type === 'error'
                 }"
                 class="px-6 py-4 rounded-lg shadow-lg text-white max-w-sm">
                <div class="flex items-center justify-between">
                    <span x-text="notification.message"></span>
                    <button @click="removeNotification(notification.id)" class="mr-2 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Main Container -->
    <div class="flex h-screen overflow-hidden">

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen"
             @click="toggleSidebar()"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
             x-cloak></div>

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'"
               class="fixed inset-y-0 right-0 z-40 w-80 lg:w-64 bg-white dark:bg-gray-800 shadow-xl transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-brain text-blue-600 text-xl"></i>
                    </div>
                    <h1 class="text-xl font-bold text-white">CMIS</h1>
                </div>
                <button @click="toggleSidebar()" class="lg:hidden text-white hover:bg-white/10 p-2 rounded-lg transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="px-3 sm:px-4 py-4 sm:py-6 space-y-1 sm:space-y-2 overflow-y-auto h-[calc(100vh-4rem)]">
                @php
                    $currentOrg = auth()->user()->active_org_id ?? auth()->user()->current_org_id ?? auth()->user()->org_id ?? request()->route('org');
                @endphp

                @if($currentOrg)
                <a href="{{ route('orgs.dashboard.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('dashboard.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-home text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">الرئيسية</span>
                </a>

                <div class="pt-3 sm:pt-4 pb-1 sm:pb-2 text-[10px] sm:text-xs font-semibold text-gray-400 uppercase px-3 sm:px-4">الإدارة</div>

                <a href="{{ route('orgs.index') }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('orgs.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-building text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">المؤسسات</span>
                </a>

                <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('campaigns.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-bullhorn text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">الحملات</span>
                </a>

                <div class="pt-3 sm:pt-4 pb-1 sm:pb-2 text-[10px] sm:text-xs font-semibold text-gray-400 uppercase px-3 sm:px-4">المحتوى</div>

                <a href="{{ route('orgs.creative.assets.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('creative.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-palette text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">الإبداع</span>
                </a>

                <a href="{{ route('orgs.social.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('social.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-share-alt text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3 text-sm sm:text-base">القنوات الاجتماعية</span>
                </a>

                <div class="pt-3 sm:pt-4 pb-1 sm:pb-2 text-[10px] sm:text-xs font-semibold text-gray-400 uppercase px-3 sm:px-4">التحليلات</div>

                <a href="{{ route('orgs.analytics.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('analytics.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-chart-line text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">التحليلات</span>
                </a>

                <div class="pt-3 sm:pt-4 pb-1 sm:pb-2 text-[10px] sm:text-xs font-semibold text-gray-400 uppercase px-3 sm:px-4">الذكاء الاصطناعي</div>

                <a href="{{ route('orgs.ai.index', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('ai.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-robot text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">الذكاء الاصطناعي</span>
                </a>

                <div class="pt-3 sm:pt-4 pb-1 sm:pb-2 text-[10px] sm:text-xs font-semibold text-gray-400 uppercase px-3 sm:px-4">الإعدادات</div>

                <a href="{{ route('orgs.products', ['org' => $currentOrg]) }}"
                   class="flex items-center px-3 sm:px-4 py-2.5 sm:py-3 text-sm sm:text-base text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('orgs.products') || request()->routeIs('orgs.services') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-box text-base sm:text-lg w-5 sm:w-6"></i>
                    <span class="mr-2 sm:mr-3">المنتجات</span>
                </a>
                @else
                <div class="px-4 py-3 text-gray-600 dark:text-gray-400 text-sm">
                    الرجاء اختيار منظمة للمتابعة
                </div>
                @endif

            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Navigation Bar -->
            <header class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">

                <div class="flex items-center space-x-2 sm:space-x-4 space-x-reverse">
                    <button @click="toggleSidebar()" class="p-2 text-gray-600 dark:text-gray-300 lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas fa-bars text-lg sm:text-xl"></i>
                    </button>

                    <!-- Organization Switcher -->
                    <div class="relative" x-data="orgSwitcher()" x-init="init()">
                        <!-- Desktop & Tablet: Dropdown Button -->
                        <button @click="toggleOrgMenu()"
                                :aria-expanded="orgMenuOpen"
                                aria-label="تبديل المؤسسة"
                                class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800 text-gray-700 dark:text-gray-300 rounded-lg hover:shadow-md transition-all">
                            <div class="w-7 h-7 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-xs"
                                 x-text="currentOrg ? currentOrg.name.substring(0, 2).toUpperCase() : 'XX'"></div>
                            <span class="font-medium max-w-[150px] truncate" x-text="currentOrg ? currentOrg.name : 'تحميل...'"></span>
                            <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': orgMenuOpen }"></i>
                        </button>

                        <!-- Mobile: Compact Button -->
                        <button @click="toggleOrgMenu()"
                                :aria-expanded="orgMenuOpen"
                                aria-label="تبديل المؤسسة"
                                class="sm:hidden flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg text-white font-bold text-sm shadow-md">
                            <span x-text="currentOrg ? currentOrg.name.substring(0, 2).toUpperCase() : 'XX'"></span>
                        </button>

                        <!-- Mobile Backdrop -->
                        <div x-show="orgMenuOpen"
                             @click="orgMenuOpen = false"
                             class="fixed inset-0 bg-black bg-opacity-50 z-[80] sm:hidden"
                             x-cloak></div>

                        <!-- Desktop: Dropdown | Mobile: Bottom Sheet -->
                        <div x-show="orgMenuOpen"
                             @click.away="orgMenuOpen = false"
                             class="fixed left-0 right-0 bottom-0 sm:absolute sm:left-0 sm:right-auto sm:top-full sm:mt-2
                                    w-full sm:w-80
                                    bg-white dark:bg-gray-800
                                    rounded-t-2xl sm:rounded-lg
                                    shadow-2xl sm:shadow-xl
                                    border-t border-gray-200 dark:border-gray-700 sm:border
                                    z-[90]
                                    max-h-[70vh] sm:max-h-96 overflow-hidden"
                             x-cloak>

                            <!-- Mobile Pull Indicator -->
                            <div class="sm:hidden flex justify-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>

                            <!-- Header -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">المؤسسات المتاحة</h3>

                                <!-- Search Bar -->
                                <div class="mt-3 relative">
                                    <input type="text"
                                           x-model="searchQuery"
                                           @input="filterOrgs()"
                                           placeholder="البحث عن مؤسسة..."
                                           class="w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                </div>
                            </div>

                            <!-- Organizations List -->
                            <div class="overflow-y-auto max-h-[50vh] sm:max-h-64">
                                <!-- Loading State -->
                                <div x-show="loading" class="p-8 text-center">
                                    <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                                    <p class="text-sm text-gray-500 mt-2">جارٍ التحميل...</p>
                                </div>

                                <!-- Empty State -->
                                <template x-if="!loading && filteredOrgs.length === 0">
                                    <div class="p-8 text-center text-gray-500">
                                        <i class="fas fa-building text-3xl mb-2"></i>
                                        <p class="text-sm">لا توجد مؤسسات</p>
                                    </div>
                                </template>

                                <!-- Organizations -->
                                <template x-for="org in filteredOrgs" :key="org.org_id">
                                    <button @click="switchOrg(org.org_id)"
                                            :disabled="org.is_current || switching"
                                            class="w-full px-4 py-3 sm:py-4 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0 disabled:opacity-50"
                                            :class="{ 'bg-blue-50 dark:bg-blue-900/20': org.is_current }">

                                        <!-- Org Icon/Avatar -->
                                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                             x-text="org.name.substring(0, 2).toUpperCase()"></div>

                                        <!-- Org Info -->
                                        <div class="flex-1 text-right min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-gray-900 dark:text-white text-sm truncate"
                                                   x-text="org.name"></p>
                                                <i x-show="org.is_current"
                                                   class="fas fa-check-circle text-green-500 text-sm flex-shrink-0"></i>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"
                                               x-text="`${org.currency || 'BHD'} • ${org.default_locale || 'ar-BH'}`"></p>
                                        </div>

                                        <!-- Loading Spinner (when switching) -->
                                        <i x-show="switching && org.org_id === switchingToOrgId"
                                           class="fas fa-spinner fa-spin text-blue-600 text-sm"></i>
                                    </button>
                                </template>
                            </div>

                            <!-- Footer: Create New Org (Optional) -->
                            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                                <a href="{{ route('orgs.create') }}"
                                   class="flex items-center justify-center gap-2 w-full px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>إنشاء مؤسسة جديدة</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="relative hidden sm:block" x-data="{ searchOpen: false }">
                        <button @click="searchOpen = !searchOpen" class="flex items-center px-3 sm:px-4 py-1.5 sm:py-2 text-sm sm:text-base text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <i class="fas fa-search ml-2 text-sm"></i>
                            <span class="hidden md:inline">بحث...</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-1.5 sm:space-x-4 space-x-reverse">

                    <!-- Dark Mode Toggle -->
                    <button @click="toggleDarkMode()" class="p-1.5 sm:p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <i class="fas text-sm sm:text-base" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative" x-data="notificationsWidget()" x-init="init()">
                        <button @click="toggleNotifications()" class="relative p-1.5 sm:p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <i class="fas fa-bell text-sm sm:text-base"></i>
                            <span x-show="unreadCount > 0"
                                  x-text="unreadCount > 9 ? '9+' : unreadCount"
                                  class="absolute -top-0.5 sm:-top-1 -left-0.5 sm:-left-1 min-w-[16px] sm:min-w-[20px] h-4 sm:h-5 text-[10px] sm:text-xs flex items-center justify-center bg-red-500 text-white font-bold rounded-full px-0.5 sm:px-1">
                            </span>
                        </button>

                        <div x-show="notifOpen"
                             @click.away="notifOpen = false"
                             x-transition
                             class="absolute left-0 mt-2 w-[calc(100vw-2rem)] sm:w-80 max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
                             x-cloak>
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 dark:text-white">الإشعارات</h3>
                                <span x-show="unreadCount > 0" class="text-xs text-gray-500" x-text="`${unreadCount} غير مقروء`"></span>
                            </div>

                            <!-- Loading State -->
                            <div x-show="loading" class="p-8 text-center">
                                <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                            </div>

                            <!-- Notifications List -->
                            <div x-show="!loading" class="max-h-96 overflow-y-auto">
                                <template x-if="notifications.length === 0">
                                    <div class="p-8 text-center text-gray-500">
                                        <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                        <p class="text-sm">لا توجد إشعارات</p>
                                    </div>
                                </template>

                                <template x-for="notification in notifications" :key="notification.id">
                                    <div @click="markAsRead(notification.id)"
                                         class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition"
                                         :class="{ 'bg-blue-50 dark:bg-blue-900/20': !notification.read }">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mr-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                     :class="getNotificationColor(notification.type)">
                                                    <i :class="getNotificationIcon(notification.type)" class="text-sm"></i>
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-gray-800 dark:text-gray-200" x-text="notification.message"></p>
                                                <p class="text-xs text-gray-500 mt-1" x-text="notification.time"></p>
                                            </div>
                                            <div x-show="!notification.read" class="flex-shrink-0">
                                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="p-3 text-center border-t border-gray-200 dark:border-gray-700">
                                @php
                                    $currentOrg = auth()->user()->active_org_id ?? auth()->user()->current_org_id ?? auth()->user()->org_id ?? request()->route('org');
                                @endphp
                                @if($currentOrg)
                                <a href="{{ route('orgs.settings.index', ['org' => $currentOrg]) }}" class="text-sm text-blue-600 hover:text-blue-700">عرض جميع الإشعارات</a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen"
                                class="flex items-center space-x-1 sm:space-x-2 space-x-reverse p-1 sm:p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                :aria-expanded="userMenuOpen"
                                aria-label="قائمة المستخدم">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=667eea&color=fff"
                                 class="w-7 h-7 sm:w-8 sm:h-8 rounded-full"
                                 alt="صورة المستخدم">
                            <div class="text-right hidden lg:block">
                                <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">{{ auth()->user()->name ?? 'المستخدم' }}</p>
                                <p class="text-[10px] sm:text-xs text-gray-500">مدير النظام</p>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] sm:text-xs text-gray-600 dark:text-gray-300 hidden sm:inline"></i>
                        </button>

                        <!-- Mobile Backdrop -->
                        <div x-show="userMenuOpen"
                             @click="userMenuOpen = false"
                             x-transition:enter="transition-opacity ease-out duration-200"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition-opacity ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="fixed inset-0 bg-black bg-opacity-50 z-[60] md:hidden"
                             x-cloak></div>

                        <!-- Desktop: Dropdown | Mobile: Bottom Sheet -->
                        <div x-show="userMenuOpen"
                             @click.away="userMenuOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 md:scale-95 translate-y-full md:translate-y-0"
                             x-transition:enter-end="opacity-100 md:scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 md:scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 md:scale-95 translate-y-full md:translate-y-0"
                             class="fixed left-0 right-0 bottom-0 md:absolute md:left-0 md:right-auto md:top-full md:bottom-auto md:mt-2
                                    w-full md:w-56
                                    bg-white dark:bg-gray-800
                                    rounded-t-2xl md:rounded-lg
                                    shadow-2xl md:shadow-xl
                                    border-t-2 md:border md:border-gray-200
                                    border-gray-300 dark:border-gray-700
                                    z-[70]
                                    max-h-[70vh] md:max-h-auto overflow-y-auto"
                             x-cloak>

                            <!-- Mobile Handle (Pull Indicator) -->
                            <div class="md:hidden flex justify-center py-2 border-b border-gray-200 dark:border-gray-700">
                                <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>

                            <!-- User Info Header (Mobile Only) -->
                            <div class="md:hidden px-4 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-700">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=Admin&background=667eea&color=fff"
                                         class="w-12 h-12 rounded-full ring-2 ring-white dark:ring-gray-600"
                                         alt="صورة المستخدم">
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ auth()->user()->name ?? 'المستخدم' }}</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">مدير النظام</p>
                                    </div>
                                </div>
                            </div>

                            @php
                                $currentOrg = auth()->user()->active_org_id ?? auth()->user()->current_org_id ?? auth()->user()->org_id ?? request()->route('org');
                            @endphp

                            <!-- Menu Items -->
                            <div class="py-2">
                                <a href="{{ route('profile') }}"
                                   class="flex items-center gap-3 px-4 md:px-4 py-4 md:py-3 text-base md:text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors md:rounded-t-lg">
                                    <i class="fas fa-user text-blue-600 dark:text-blue-400 w-5 text-center"></i>
                                    <span class="font-medium md:font-normal">الملف الشخصي</span>
                                </a>

                                @if($currentOrg)
                                <a href="{{ route('orgs.settings.index', ['org' => $currentOrg]) }}"
                                   class="flex items-center gap-3 px-4 md:px-4 py-4 md:py-3 text-base md:text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-cog text-gray-600 dark:text-gray-400 w-5 text-center"></i>
                                    <span class="font-medium md:font-normal">الإعدادات</span>
                                </a>
                                @endif
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700">

                            <form method="POST" action="{{ route('logout') }}" class="py-2">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-3 w-full text-right px-4 md:px-4 py-4 md:py-3 text-base md:text-sm text-red-600 dark:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors md:rounded-b-lg font-semibold md:font-normal">
                                    <i class="fas fa-sign-out-alt w-5 text-center"></i>
                                    <span>تسجيل الخروج</span>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-3 sm:p-4 md:p-6">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Scripts -->
    <script>
        // App Layout Controller
        function appLayout() {
            return {
                sidebarOpen: false,
                darkMode: false,

                init() {
                    // Only auto-open sidebar on desktop (>= 1024px)
                    this.sidebarOpen = window.innerWidth >= 1024;

                    // Handle window resize
                    window.addEventListener('resize', () => {
                        // Auto-open on desktop, auto-close on mobile
                        if (window.innerWidth >= 1024) {
                            this.sidebarOpen = true;
                        } else {
                            this.sidebarOpen = false;
                        }
                    });

                    // Load dark mode preference
                    const savedDarkMode = localStorage.getItem('darkMode');
                    if (savedDarkMode !== null) {
                        this.darkMode = savedDarkMode === 'true';
                    }
                },

                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                }
            };
        }

        // Organization Switcher
        function orgSwitcher() {
            return {
                orgMenuOpen: false,
                loading: false,
                switching: false,
                switchingToOrgId: null,
                organizations: [],
                filteredOrgs: [],
                currentOrg: null,
                searchQuery: '',

                async init() {
                    await this.loadOrganizations();
                },

                async loadOrganizations() {
                    this.loading = true;
                    try {
                        const response = await fetch('/api/user/organizations', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.organizations = data.data.organizations || [];
                            this.filteredOrgs = this.organizations;
                            this.currentOrg = this.organizations.find(org => org.is_current) || null;
                        } else {
                            console.error('Failed to load organizations:', response.statusText);
                        }
                    } catch (error) {
                        console.error('Error loading organizations:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                toggleOrgMenu() {
                    this.orgMenuOpen = !this.orgMenuOpen;
                    if (this.orgMenuOpen && this.organizations.length === 0) {
                        this.loadOrganizations();
                    }
                },

                filterOrgs() {
                    const query = this.searchQuery.toLowerCase().trim();
                    if (!query) {
                        this.filteredOrgs = this.organizations;
                    } else {
                        this.filteredOrgs = this.organizations.filter(org =>
                            org.name.toLowerCase().includes(query)
                        );
                    }
                },

                async switchOrg(orgId) {
                    if (this.switching || orgId === this.currentOrg?.org_id) return;

                    this.switching = true;
                    this.switchingToOrgId = orgId;

                    try {
                        const response = await fetch('/api/user/switch-organization', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({ org_id: orgId })
                        });

                        if (response.ok) {
                            const data = await response.json();

                            // Update current org
                            this.organizations = this.organizations.map(org => ({
                                ...org,
                                is_current: org.org_id === orgId
                            }));
                            this.currentOrg = this.organizations.find(org => org.org_id === orgId);
                            this.filteredOrgs = this.organizations;

                            // Show success message
                            this.showSuccessMessage(data.message || 'تم تبديل المؤسسة بنجاح');

                            // Close menu
                            this.orgMenuOpen = false;

                            // Reload page to refresh all org-specific data
                            setTimeout(() => {
                                window.location.reload();
                            }, 500);
                        } else {
                            const error = await response.json();
                            this.showErrorMessage(error.message || 'فشل تبديل المؤسسة');
                        }
                    } catch (error) {
                        console.error('Error switching organization:', error);
                        this.showErrorMessage('حدث خطأ أثناء تبديل المؤسسة');
                    } finally {
                        this.switching = false;
                        this.switchingToOrgId = null;
                    }
                },

                showSuccessMessage(message) {
                    // Create temporary toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-[100] flex items-center gap-2 animate-fade-in-down';
                    toast.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <span>${message}</span>
                    `;
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                },

                showErrorMessage(message) {
                    // Create temporary toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed top-4 left-1/2 -translate-x-1/2 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-[100] flex items-center gap-2 animate-fade-in-down';
                    toast.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${message}</span>
                    `;
                    document.body.appendChild(toast);

                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            };
        }

        // Notifications Widget
        function notificationsWidget() {
            return {
                notifOpen: false,
                loading: false,
                notifications: [],
                unreadCount: 0,
                refreshInterval: null,

                async init() {
                    await this.loadNotifications();
                    // Auto-refresh every 30 seconds
                    this.refreshInterval = setInterval(() => {
                        this.loadNotifications();
                    }, 30000);
                },

                async loadNotifications() {
                    this.loading = true;
                    try {
                        const response = await fetch('/notifications/latest', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        if (response.ok) {
                            const data = await response.json();
                            this.notifications = data.notifications || [];
                            this.unreadCount = this.notifications.filter(n => !n.read).length;
                        } else {
                            // Fallback to sample data if API not ready
                            this.loadSampleNotifications();
                        }
                    } catch (error) {
                        console.error('Failed to load notifications:', error);
                        // Fallback to sample data
                        this.loadSampleNotifications();
                    } finally {
                        this.loading = false;
                    }
                },

                loadSampleNotifications() {
                    this.notifications = [
                        { id: 1, type: 'campaign', message: 'تم إطلاق حملة "عروض الصيف" بنجاح', time: 'منذ 5 دقائق', read: false },
                        { id: 2, type: 'analytics', message: 'تحديث في أداء الحملات - زيادة 15% في التحويلات', time: 'منذ ساعة', read: false },
                        { id: 3, type: 'integration', message: 'تم ربط حساب Meta Ads بنجاح', time: 'منذ 3 ساعات', read: true },
                        { id: 4, type: 'user', message: 'تمت إضافة عضو جديد إلى الفريق', time: 'منذ يوم', read: true }
                    ];
                    this.unreadCount = this.notifications.filter(n => !n.read).length;
                },

                toggleNotifications() {
                    this.notifOpen = !this.notifOpen;
                    if (this.notifOpen && this.notifications.length === 0) {
                        this.loadNotifications();
                    }
                },

                async markAsRead(notificationId) {
                    try {
                        // Find notification
                        const notification = this.notifications.find(n => n.id === notificationId);
                        if (!notification || notification.read) return;

                        // Try to mark as read on server
                        const response = await fetch(`/notifications/${notificationId}/read`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });

                        if (response.ok || response.status === 404) {
                            // Mark as read locally
                            notification.read = true;
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                        }
                    } catch (error) {
                        console.error('Failed to mark notification as read:', error);
                        // Still mark as read locally
                        const notification = this.notifications.find(n => n.id === notificationId);
                        if (notification && !notification.read) {
                            notification.read = true;
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                        }
                    }
                },

                getNotificationIcon(type) {
                    const icons = {
                        'campaign': 'fas fa-bullhorn',
                        'analytics': 'fas fa-chart-line',
                        'integration': 'fas fa-plug',
                        'user': 'fas fa-user',
                        'creative': 'fas fa-palette',
                        'system': 'fas fa-cog'
                    };
                    return icons[type] || 'fas fa-bell';
                },

                getNotificationColor(type) {
                    const colors = {
                        'campaign': 'bg-blue-100 text-blue-600',
                        'analytics': 'bg-green-100 text-green-600',
                        'integration': 'bg-purple-100 text-purple-600',
                        'user': 'bg-yellow-100 text-yellow-600',
                        'creative': 'bg-pink-100 text-pink-600',
                        'system': 'bg-gray-100 text-gray-600'
                    };
                    return colors[type] || 'bg-gray-100 text-gray-600';
                }
            };
        }

        // Notification Manager
        function notificationManager() {
            return {
                notifications: [],
                notificationId: 0,

                addNotification(detail) {
                    const id = this.notificationId++;
                    const notification = {
                        id: id,
                        message: detail.message || 'إشعار',
                        type: detail.type || 'info',
                        show: true
                    };

                    this.notifications.push(notification);

                    setTimeout(() => {
                        this.removeNotification(id);
                    }, 5000);
                },

                removeNotification(id) {
                    const index = this.notifications.findIndex(n => n.id === id);
                    if (index > -1) {
                        this.notifications[index].show = false;
                        setTimeout(() => {
                            this.notifications.splice(index, 1);
                        }, 300);
                    }
                }
            };
        }

        // Global notification function
        window.notify = function(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        };

        // Axios setup for CSRF token
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            const token = document.head.querySelector('meta[name="csrf-token"]');
            if (token) {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
            }
        }
    </script>

    @stack('scripts')
</body>
</html>

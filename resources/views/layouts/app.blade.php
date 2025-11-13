<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMIS') }} - @yield('title', 'لوحة التحكم')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        .sidebar-item { transition: all 0.2s ease; }
        .sidebar-item:hover { transform: translateX(-2px); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .gradient-blue { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .nav-active { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease; }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div x-data="{ sidebarOpen: false, notificationsOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <div :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0'"
             class="fixed inset-y-0 right-0 z-40 w-72 gradient-bg shadow-2xl md:relative transition-transform duration-300">
            <!-- Logo Section -->
            <div class="p-6 border-b border-white/20">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3">
                        <i class="fas fa-rocket text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">CMIS Platform</h1>
                        <p class="text-white/70 text-xs">نظام إدارة التسويق</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-4 space-y-2">
                <a href="{{ route('dashboard.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('dashboard.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                    <i class="fas fa-home text-lg w-6"></i>
                    <span class="font-medium">الرئيسية</span>
                </a>

                <a href="{{ route('campaigns.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('campaigns.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                    <i class="fas fa-bullhorn text-lg w-6"></i>
                    <span class="font-medium">الحملات</span>
                </a>

                {{-- TODO: Implement content.index route --}}
                {{-- <a href="{{ route('content.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('content.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                    <i class="fas fa-file-alt text-lg w-6"></i>
                    <span class="font-medium">المحتوى</span>
                </a> --}}

                <a href="{{ route('creative-assets.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('creative-assets.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                    <i class="fas fa-images text-lg w-6"></i>
                    <span class="font-medium">الملفات الإبداعية</span>
                </a>

                @can('viewAny', App\Models\User::class)
                <a href="{{ route('analytics.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('analytics.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                    <i class="fas fa-chart-line text-lg w-6"></i>
                    <span class="font-medium">التحليلات</span>
                </a>
                @endcan

                <div class="pt-4 border-t border-white/20 mt-4">
                    <p class="text-white/50 text-xs font-medium px-4 mb-2">الأدوات</p>

                    @can('viewAny', App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('users.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                        <i class="fas fa-users text-lg w-6"></i>
                        <span class="font-medium">المستخدمون</span>
                    </a>
                    @endcan

                    <a href="{{ route('settings.index') }}" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('settings.*') ? 'bg-white/20 text-white shadow-lg' : 'text-white/80 hover:bg-white/10' }}">
                        <i class="fas fa-cog text-lg w-6"></i>
                        <span class="font-medium">الإعدادات</span>
                    </a>
                </div>
            </nav>

            <!-- User Card at Bottom -->
            <div class="absolute bottom-0 right-0 left-0 p-4 border-t border-white/20">
                <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3">
                    @auth
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-lg p-2">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white font-medium text-sm">{{ Auth::user()->name }}</p>
                            <p class="text-white/60 text-xs">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    @endauth
                </div>
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
                            <h2 class="text-2xl font-bold text-gray-800">@yield('page-title', 'لوحة التحكم')</h2>
                            <p class="text-sm text-gray-500">@yield('page-subtitle', 'مرحباً بك في نظام إدارة المحتوى والحملات')</p>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center gap-3">
                            <!-- Search -->
                            <div class="hidden lg:block">
                                <div class="relative">
                                    <input type="text" placeholder="بحث..."
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

                                <div x-show="open" @click.away="open = false" x-transition
                                     class="absolute left-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                                    <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                                        <h3 class="text-white font-bold">الإشعارات</h3>
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer border-b">
                                            <p class="text-sm font-medium text-gray-800">حملة جديدة تم إنشاؤها</p>
                                            <p class="text-xs text-gray-500 mt-1">منذ 5 دقائق</p>
                                        </div>
                                        <div class="p-4 hover:bg-gray-50 cursor-pointer">
                                            <p class="text-sm font-medium text-gray-800">تقرير أسبوعي جاهز</p>
                                            <p class="text-xs text-gray-500 mt-1">منذ ساعة</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- User Menu -->
                            @auth
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-xl hover:bg-gray-100 transition">
                                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg w-9 h-9 flex items-center justify-center font-bold">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <i class="fas fa-chevron-down text-gray-600 text-xs"></i>
                                </button>

                                <div x-show="open" @click.away="open = false" x-transition
                                     class="absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden">
                                    <div class="p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                                        <p class="text-white font-bold">{{ Auth::user()->name }}</p>
                                        <p class="text-white/80 text-xs">{{ Auth::user()->email }}</p>
                                    </div>
                                    <div class="p-2">
                                        <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                                            <i class="fas fa-user text-gray-600 w-5"></i>
                                            <span class="text-sm text-gray-700">الملف الشخصي</span>
                                        </a>
                                        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition">
                                            <i class="fas fa-cog text-gray-600 w-5"></i>
                                            <span class="text-sm text-gray-700">الإعدادات</span>
                                        </a>
                                        <hr class="my-2">
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 transition">
                                                <i class="fas fa-sign-out-alt w-5"></i>
                                                <span class="text-sm font-medium">تسجيل الخروج</span>
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
                    <p>&copy; 2025 CMIS Platform. جميع الحقوق محفوظة.</p>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-indigo-600 transition">المساعدة</a>
                        <a href="#" class="hover:text-indigo-600 transition">الدعم الفني</a>
                        <a href="#" class="hover:text-indigo-600 transition">الشروط</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Overlay for Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 md:hidden" x-transition></div>

    @stack('scripts')
</body>
</html>

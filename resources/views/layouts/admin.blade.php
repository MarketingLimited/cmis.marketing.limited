<!DOCTYPE html>
<html lang="ar" dir="rtl" x-data="{ sidebarOpen: true, darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMIS') - لوحة التحكم</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Icons: Heroicons via CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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

        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 right-0 z-40 w-64 bg-white dark:bg-gray-800 shadow-xl transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">

            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 bg-gradient-to-r from-blue-600 to-purple-600">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <i class="fas fa-brain text-blue-600 text-xl"></i>
                    </div>
                    <h1 class="text-xl font-bold text-white">CMIS</h1>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="px-4 py-6 space-y-2 overflow-y-auto h-[calc(100vh-4rem)]">

                <a href="{{ route('dashboard.index') }}"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition {{ request()->routeIs('dashboard.*') ? 'bg-blue-50 text-blue-600 font-semibold' : '' }}">
                    <i class="fas fa-home text-lg w-6"></i>
                    <span class="mr-3">الرئيسية</span>
                </a>

                <div class="pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase">الإدارة</div>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-building text-lg w-6"></i>
                    <span class="mr-3">المؤسسات</span>
                </a>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-bullhorn text-lg w-6"></i>
                    <span class="mr-3">الحملات</span>
                </a>

                <div class="pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase">المحتوى</div>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-palette text-lg w-6"></i>
                    <span class="mr-3">الإبداع</span>
                </a>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-share-alt text-lg w-6"></i>
                    <span class="mr-3">القنوات الاجتماعية</span>
                </a>

                <div class="pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase">التحليلات</div>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-chart-line text-lg w-6"></i>
                    <span class="mr-3">التحليلات</span>
                </a>

                <div class="pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase">الذكاء الاصطناعي</div>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-robot text-lg w-6"></i>
                    <span class="mr-3">الذكاء الاصطناعي</span>
                </a>

                <div class="pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase">الإعدادات</div>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-plug text-lg w-6"></i>
                    <span class="mr-3">التكاملات</span>
                </a>

                <a href="#"
                   class="flex items-center px-4 py-3 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-blue-50 dark:hover:bg-gray-700 transition opacity-50 cursor-not-allowed">
                    <i class="fas fa-box text-lg w-6"></i>
                    <span class="mr-3">العروض</span>
                </a>

            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Top Navigation Bar -->
            <header class="flex items-center justify-between h-16 px-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">

                <div class="flex items-center space-x-4 space-x-reverse">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 dark:text-gray-300 lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="relative" x-data="{ searchOpen: false }">
                        <button @click="searchOpen = !searchOpen" class="flex items-center px-4 py-2 text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            <i class="fas fa-search ml-2"></i>
                            <span>بحث...</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center space-x-4 space-x-reverse">

                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen" class="relative p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <i class="fas fa-bell"></i>
                            <span class="absolute top-0 left-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <div x-show="notifOpen"
                             @click.away="notifOpen = false"
                             x-transition
                             class="absolute left-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
                             x-cloak>
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="font-semibold text-gray-900 dark:text-white">الإشعارات</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <p class="text-sm text-gray-800 dark:text-gray-200">تم إطلاق حملة جديدة بنجاح</p>
                                    <p class="text-xs text-gray-500 mt-1">منذ 5 دقائق</p>
                                </div>
                                <div class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                                    <p class="text-sm text-gray-800 dark:text-gray-200">تحديث في أداء الحملات</p>
                                    <p class="text-xs text-gray-500 mt-1">منذ ساعة</p>
                                </div>
                            </div>
                            <div class="p-3 text-center border-t border-gray-200 dark:border-gray-700">
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-700">عرض جميع الإشعارات</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ userMenuOpen: false }">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-2 space-x-reverse p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=667eea&color=fff" class="w-8 h-8 rounded-full">
                            <div class="text-right hidden md:block">
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ auth()->user()->name ?? 'المستخدم' }}</p>
                                <p class="text-xs text-gray-500">مدير النظام</p>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-600 dark:text-gray-300"></i>
                        </button>

                        <div x-show="userMenuOpen"
                             @click.away="userMenuOpen = false"
                             x-transition
                             class="absolute left-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50"
                             x-cloak>
                            <a href="#" class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg">
                                <i class="fas fa-user ml-2"></i> الملف الشخصي
                            </a>
                            <a href="#" class="block px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-cog ml-2"></i> الإعدادات
                            </a>
                            <hr class="border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-right px-4 py-3 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-lg">
                                    <i class="fas fa-sign-out-alt ml-2"></i> تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
                @yield('content')
            </main>

        </div>
    </div>

    <!-- Scripts -->
    <script>
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

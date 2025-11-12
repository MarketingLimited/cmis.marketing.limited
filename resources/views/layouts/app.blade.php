<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMIS') }} - @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
</head>
<body class="bg-gray-50">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <div :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-900 lg:translate-x-0 lg:static transition-transform duration-300">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-white">CMIS</h1>
            </div>
            <nav class="mt-6">
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-gray-100' : '' }}">
                    <i class="fas fa-home w-5"></i><span class="mx-3">Dashboard</span>
                </a>
                <a href="{{ route('campaigns.index') }}" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100 {{ request()->routeIs('campaigns.*') ? 'bg-gray-800 text-gray-100' : '' }}">
                    <i class="fas fa-bullhorn w-5"></i><span class="mx-3">Campaigns</span>
                </a>
                <a href="{{ route('content.index') }}" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100 {{ request()->routeIs('content.*') ? 'bg-gray-800 text-gray-100' : '' }}">
                    <i class="fas fa-file-alt w-5"></i><span class="mx-3">Content</span>
                </a>
                <a href="{{ route('assets.index') }}" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100 {{ request()->routeIs('assets.*') ? 'bg-gray-800 text-gray-100' : '' }}">
                    <i class="fas fa-images w-5"></i><span class="mx-3">Assets</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100">
                    <i class="fas fa-chart-line w-5"></i><span class="mx-3">Analytics</span>
                </a>
                @can('viewAny', App\Models\User::class)
                <a href="{{ route('users.index') }}" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100 {{ request()->routeIs('users.*') ? 'bg-gray-800 text-gray-100' : '' }}">
                    <i class="fas fa-users w-5"></i><span class="mx-3">Users</span>
                </a>
                @endcan
                <a href="#" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100">
                    <i class="fas fa-cog w-5"></i><span class="mx-3">Settings</span>
                </a>
            </nav>
        </div>
        <div class="flex-1">
            <header class="bg-white shadow px-6 py-4">
                <div class="flex justify-between items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500">
                        <i class="fas fa-bars text-xl"></i>
                    </button>

                    <div class="ml-auto flex items-center space-x-4">
                        @auth
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                    <i class="fas fa-user-circle text-2xl"></i>
                                    <span class="hidden md:inline">{{ Auth::user()->name }}</span>
                                    <i class="fas fa-chevron-down text-sm"></i>
                                </button>

                                <div x-show="open" @click.away="open = false"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-user mr-2"></i>Profile
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cog mr-2"></i>Settings
                                    </a>
                                    <hr class="my-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth
                    </div>
                </div>
            </header>
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

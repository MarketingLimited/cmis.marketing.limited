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
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-100 bg-gray-800">
                    <i class="fas fa-home w-5"></i><span class="mx-3">Dashboard</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-gray-100">
                    <i class="fas fa-bullhorn w-5"></i><span class="mx-3">Campaigns</span>
                </a>
            </nav>
        </div>
        <div class="flex-1">
            <header class="bg-white shadow px-6 py-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </header>
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

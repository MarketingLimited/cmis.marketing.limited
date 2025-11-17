<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CMIS') }} - @yield('title', 'مرحباً')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-indigo-100 to-purple-100 min-h-screen">
    <div class="min-h-screen flex flex-col justify-center items-center p-4">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <div class="inline-flex items-center gap-3 bg-white rounded-2xl p-4 shadow-lg">
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl p-3">
                    <i class="fas fa-rocket text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-bold text-gray-900">CMIS Platform</h1>
                    <p class="text-gray-500 text-sm">نظام إدارة التسويق المتكامل</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="w-full max-w-md">
            @yield('content')
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600 text-sm">
            <p>&copy; {{ date('Y') }} CMIS Platform. جميع الحقوق محفوظة.</p>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

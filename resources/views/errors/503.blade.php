@extends('layouts.guest')

@section('title', '503 - Service Unavailable')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <h1 class="text-9xl font-bold text-yellow-600">503</h1>
            <div class="text-6xl mb-4">🔧</div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                الخدمة غير متاحة مؤقتاً
            </h2>
            <p class="text-lg text-gray-600 mb-4">
                نعمل حالياً على صيانة وتحديث النظام
            </p>
            <p class="text-sm text-gray-500 mb-6">
                سيعود النظام للعمل قريباً. شكراً على صبرك وتفهمك
            </p>
        </div>

        <div class="bg-blue-50 border-r-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="mr-3">
                    <p class="text-sm text-blue-700 text-right">
                        إذا كنت بحاجة إلى المساعدة، يرجى التواصل مع فريق الدعم الفني
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <button
                onclick="location.reload()"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                إعادة المحاولة
            </button>
        </div>
    </div>
</div>
@endsection

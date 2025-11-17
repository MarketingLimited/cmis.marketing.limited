@extends('layouts.admin')

@section('title', 'الباقات')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">الباقات</h1>
            <p class="mt-2 text-gray-600">إدارة باقات المنتجات والخدمات</p>
        </div>
        @can('create', App\Models\Offering::class)
        <a href="{{ route('bundles.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
            <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            باقة جديدة
        </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- Bundle cards -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border-t-4 border-purple-600">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">باقة البداية</h3>
                    <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">شائعة</span>
                </div>
                <p class="text-sm text-gray-600 mb-4">مثالية للشركات الناشئة والأعمال الصغيرة</p>

                <div class="mb-4">
                    <span class="text-3xl font-bold text-gray-900">2,500</span>
                    <span class="text-gray-600">ر.س/شهرياً</span>
                </div>

                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        3 منتجات رقمية
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        خدمة استشارية
                    </li>
                    <li class="flex items-center text-sm text-gray-600">
                        <svg class="h-5 w-5 text-green-500 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        دعم فني 24/7
                    </li>
                </ul>

                <button class="w-full bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 transition-colors">
                    اختر الباقة
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

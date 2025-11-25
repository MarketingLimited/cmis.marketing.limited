@extends('layouts.admin')

@section('page-title', $product->name ?? 'تفاصيل المنتج')
@section('page-subtitle', 'عرض تفاصيل ومعلومات المنتج')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="max-w-6xl mx-auto">
    <x-breadcrumb :items="[
        ['label' => 'المنتجات', 'url' => route('orgs.products.index', ['org' => $currentOrg])],
        ['label' => $product->name ?? 'المنتج']
    ]" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Product Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $product->name }}</h1>
                        <p class="text-gray-600 mt-2">{{ $product->category }}</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-medium
                        {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $product->is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </div>

                <!-- Product Image -->
                @if($product->image_url)
                <div class="mb-6">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                         class="w-full h-96 object-cover rounded-lg">
                </div>
                @endif

                <!-- Description -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-3">الوصف</h3>
                    <p class="text-gray-700 leading-relaxed">{{ $product->description }}</p>
                </div>

                <!-- Features -->
                @if($product->features && count($product->features) > 0)
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-3">المميزات</h3>
                    <ul class="space-y-2">
                        @foreach($product->features as $feature)
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Specifications -->
                @if($product->metadata && isset($product->metadata['specifications']))
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-3">المواصفات</h3>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($product->metadata['specifications'] as $key => $value)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 mb-1">{{ $key }}</p>
                            <p class="font-medium text-gray-900">{{ $value }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Reviews/Ratings (if applicable) -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">التقييمات</h3>
                <div class="text-center py-8">
                    <i class="fas fa-star text-yellow-400 text-5xl mb-3"></i>
                    <p class="text-3xl font-bold text-gray-900 mb-2">4.5</p>
                    <p class="text-gray-600">من 5 (128 تقييم)</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Pricing -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                <h3 class="text-lg font-bold mb-4">السعر</h3>
                <div class="text-4xl font-bold mb-2">
                    {{ number_format($product->price, 2) }}
                    <span class="text-xl">{{ $product->currency }}</span>
                </div>
                <p class="text-white/80 text-sm mb-6">شامل ضريبة القيمة المضافة</p>

                <button class="w-full bg-white text-indigo-600 py-3 rounded-lg font-bold hover:shadow-xl transition">
                    <i class="fas fa-shopping-cart ml-2"></i>
                    إضافة للسلة
                </button>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">الإحصائيات</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">المشاهدات</span>
                        <span class="font-bold text-gray-900">1,234</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">المبيعات</span>
                        <span class="font-bold text-gray-900">89</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">المفضلة</span>
                        <span class="font-bold text-gray-900">256</span>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">منتجات ذات صلة</h3>
                <div class="space-y-3">
                    <!-- Sample related product -->
                    <a href="#" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <h4 class="font-medium text-gray-900 mb-1">منتج مشابه 1</h4>
                        <p class="text-sm text-gray-600">599 ر.س</p>
                    </a>
                    <a href="#" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <h4 class="font-medium text-gray-900 mb-1">منتج مشابه 2</h4>
                        <p class="text-sm text-gray-600">799 ر.س</p>
                    </a>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات</h3>
                <div class="space-y-2">
                    <a href="{{ route('orgs.products.edit', ['org' => $currentOrg, 'product' => $product->offering_id]) }}"
                       class="block w-full bg-indigo-50 text-indigo-600 text-center py-2 rounded-lg font-medium hover:bg-indigo-100 transition">
                        <i class="fas fa-edit ml-2"></i>
                        تعديل
                    </a>
                    <button class="w-full bg-green-50 text-green-600 py-2 rounded-lg font-medium hover:bg-green-100 transition">
                        <i class="fas fa-share-alt ml-2"></i>
                        مشاركة
                    </button>
                    <button class="w-full bg-red-50 text-red-600 py-2 rounded-lg font-medium hover:bg-red-100 transition">
                        <i class="fas fa-trash ml-2"></i>
                        حذف
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('page-title', $service->name ?? 'تفاصيل الخدمة')
@section('page-subtitle', 'عرض تفاصيل الخدمة التسويقية')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="max-w-6xl mx-auto">
    <x-breadcrumb :items="[
        ['label' => 'الخدمات', 'url' => route('orgs.services.index', ['org' => $currentOrg])],
        ['label' => $service->name ?? 'الخدمة']
    ]" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Service Info -->
            <div class="bg-white rounded-xl shadow-sm p-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $service->name }}</h1>
                        <p class="text-lg text-indigo-600 mt-2 font-medium">{{ $service->category }}</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        متاح
                    </span>
                </div>

                <!-- Icon/Image -->
                <div class="mb-8 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-12 text-center">
                    <i class="fas fa-{{ $service->icon ?? 'briefcase' }} text-8xl text-indigo-600 mb-4"></i>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">نبذة عن الخدمة</h3>
                    <p class="text-gray-700 text-lg leading-relaxed">{{ $service->description }}</p>
                </div>

                <!-- What's Included -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">ما يشمله هذه الخدمة</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($service->features && count($service->features) > 0)
                            @foreach($service->features as $feature)
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                                <i class="fas fa-check-circle text-green-500 text-xl mt-1"></i>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $feature }}</p>
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Process/How It Works -->
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">آلية العمل</h3>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">1</div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">التواصل والمناقشة</h4>
                                <p class="text-gray-600">نتواصل معك لفهم احتياجاتك وأهدافك التسويقية</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">2</div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">التخطيط والاستراتيجية</h4>
                                <p class="text-gray-600">نضع خطة عمل مفصلة واستراتيجية شاملة</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">3</div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">التنفيذ والمتابعة</h4>
                                <p class="text-gray-600">نبدأ بتنفيذ الحملات مع متابعة مستمرة للأداء</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-lg">4</div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">التحليل والتحسين</h4>
                                <p class="text-gray-600">تحليل النتائج وتحسين الأداء بشكل مستمر</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">الأسئلة الشائعة</h3>
                    <div class="space-y-3">
                        <details class="bg-gray-50 rounded-lg p-4">
                            <summary class="font-medium text-gray-900 cursor-pointer">كم تستغرق مدة الخدمة؟</summary>
                            <p class="text-gray-600 mt-2">تختلف المدة حسب نطاق المشروع، عادة من 2-6 أشهر.</p>
                        </details>
                        <details class="bg-gray-50 rounded-lg p-4">
                            <summary class="font-medium text-gray-900 cursor-pointer">هل يمكن تخصيص الخدمة؟</summary>
                            <p class="text-gray-600 mt-2">نعم، نقدم حلول مخصصة حسب احتياجاتك.</p>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Pricing -->
            <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg p-6 text-white sticky top-6">
                <h3 class="text-lg font-bold mb-4">التسعير</h3>

                <div class="mb-6">
                    <p class="text-white/80 text-sm mb-2">يبدأ من</p>
                    <div class="text-4xl font-bold mb-1">
                        {{ number_format($service->price, 0) }}
                        <span class="text-xl">{{ $service->currency }}</span>
                    </div>
                    <p class="text-white/80 text-sm">شهرياً</p>
                </div>

                <button class="w-full bg-white text-indigo-600 py-3 rounded-lg font-bold hover:shadow-xl transition mb-3">
                    <i class="fas fa-phone ml-2"></i>
                    طلب استشارة
                </button>
                <button class="w-full bg-white/10 backdrop-blur-sm text-white border border-white/30 py-3 rounded-lg font-bold hover:bg-white/20 transition">
                    <i class="fas fa-download ml-2"></i>
                    تحميل الملف التعريفي
                </button>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إحصائيات الخدمة</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-4 border-b">
                        <span class="text-gray-600">العملاء</span>
                        <span class="font-bold text-2xl text-indigo-600">150+</span>
                    </div>
                    <div class="flex items-center justify-between pb-4 border-b">
                        <span class="text-gray-600">المشاريع المكتملة</span>
                        <span class="font-bold text-2xl text-green-600">450+</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">التقييم</span>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span class="font-bold text-2xl text-gray-900">4.9</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">تواصل معنا</h3>
                <div class="space-y-3">
                    <a href="mailto:info@example.com" class="flex items-center gap-3 text-gray-700 hover:text-indigo-600 transition">
                        <i class="fas fa-envelope w-5"></i>
                        <span>info@example.com</span>
                    </a>
                    <a href="tel:+966501234567" class="flex items-center gap-3 text-gray-700 hover:text-indigo-600 transition">
                        <i class="fas fa-phone w-5"></i>
                        <span>+966 50 123 4567</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 text-gray-700 hover:text-indigo-600 transition">
                        <i class="fab fa-whatsapp w-5"></i>
                        <span>WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

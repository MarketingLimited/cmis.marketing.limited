@extends('layouts.admin')

@section('title', 'الخدمات')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">الخدمات</h1>
            <p class="mt-2 text-gray-600">إدارة خدماتك التسويقية والاستشارية</p>
        </div>
        @can('create', App\Models\Offering::class)
        <a href="{{ route('orgs.services.create', ['org' => $currentOrg]) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
            <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            خدمة جديدة
        </a>
        @endcan
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <!-- Service cards will be rendered here -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 rounded-full p-3 ml-3">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">خدمات التسويق الرقمي</h3>
                    <p class="text-sm text-gray-500">12 عميل نشط</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4">خدمات شاملة للتسويق عبر القنوات الرقمية</p>
            <div class="flex items-center justify-between">
                <span class="text-lg font-bold text-green-600">5,000 ر.س/شهرياً</span>
                <a href="#" class="text-green-600 hover:text-green-700 text-sm font-medium">عرض التفاصيل →</a>
            </div>
        </div>
    </div>
</div>
@endsection

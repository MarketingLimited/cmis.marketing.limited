@extends('layouts.guest')

@section('title', '403 - Forbidden')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mb-8">
            <h1 class="text-9xl font-bold text-red-600">403</h1>
            <div class="text-6xl mb-4">🚫</div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                الوصول محظور
            </h2>
            <p class="text-lg text-gray-600 mb-4">
                عذراً، ليس لديك صلاحية الوصول إلى هذه الصفحة
            </p>
            @if(isset($exception) && $exception->getMessage())
                <div class="bg-red-50 border-r-4 border-red-400 p-4 mb-6">
                    <p class="text-sm text-red-700 text-right">{{ $exception->getMessage() }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            @php
                $orgId = request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id ?? null;
            @endphp
            @if($orgId)
                <a
                    href="{{ route('orgs.dashboard.index', ['org' => $orgId]) }}"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    العودة إلى الصفحة الرئيسية
                </a>
            @else
                <a
                    href="{{ route('orgs.index') }}"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    اختيار المؤسسة
                </a>
            @endif

            <button
                onclick="history.back()"
                class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                العودة للخلف
            </button>
        </div>
    </div>
</div>
@endsection

@extends('layouts.guest')

@section('title', __('auth.portal_select_title'))

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-100">
    <div class="max-w-2xl w-full space-y-8">
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                {{ __('auth.portal_select_title') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                {{ __('auth.portal_select_subtitle', ['name' => auth()->user()->name]) }}
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Super Admin Dashboard Card -->
            <a href="{{ url('/super-admin/dashboard') }}"
               class="group relative bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-2 border-transparent hover:border-purple-500">
                <div class="flex flex-col items-center text-center">
                    <div class="flex-shrink-0 mb-4">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">
                        {{ __('auth.portal_super_admin_title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('auth.portal_super_admin_desc') }}
                    </p>
                    <div class="mt-4">
                        <span class="inline-flex items-center text-sm font-medium text-purple-600 group-hover:text-purple-700">
                            {{ __('auth.portal_go_to') }}
                            <svg class="ms-1 w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>

            <!-- App Dashboard Card -->
            <a href="{{ url('/orgs') }}"
               class="group relative bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 p-8 border-2 border-transparent hover:border-indigo-500">
                <div class="flex flex-col items-center text-center">
                    <div class="flex-shrink-0 mb-4">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                        {{ __('auth.portal_app_title') }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        {{ __('auth.portal_app_desc') }}
                    </p>
                    <div class="mt-4">
                        <span class="inline-flex items-center text-sm font-medium text-indigo-600 group-hover:text-indigo-700">
                            {{ __('auth.portal_go_to') }}
                            <svg class="ms-1 w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Logout Option -->
        <div class="text-center mt-8">
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
                    {{ __('auth.portal_logout') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

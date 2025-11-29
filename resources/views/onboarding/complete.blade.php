@extends('layouts.admin')

@section('title', __('onboarding.complete_title'))

@section('content')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('onboarding.index') }}" class="hover:text-blue-600 transition">
                {{ __('onboarding.title') }}
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('onboarding.complete_title') }}</span>
        </nav>
    </div>

    {{-- Completion Header --}}
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-lg p-8 text-white mb-8">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-4xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold mb-2">
                    {{ __('onboarding.congratulations') }}
                </h1>
                <p class="text-lg opacity-90">
                    {{ __('onboarding.complete_message') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Completion Stats --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            {{ __('onboarding.what_you_accomplished') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Profile --}}
            <div class="bg-blue-50 rounded-lg p-4 text-center">
                <i class="fas fa-user-circle text-3xl text-blue-600 mb-2"></i>
                <h3 class="font-medium text-gray-900">{{ __('onboarding.steps.profile_setup.title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('onboarding.completed_label') }}</p>
            </div>

            {{-- Platform --}}
            <div class="bg-purple-50 rounded-lg p-4 text-center">
                <i class="fas fa-link text-3xl text-purple-600 mb-2"></i>
                <h3 class="font-medium text-gray-900">{{ __('onboarding.steps.platform_connection.title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('onboarding.completed_label') }}</p>
            </div>

            {{-- Campaign --}}
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <i class="fas fa-rocket text-3xl text-green-600 mb-2"></i>
                <h3 class="font-medium text-gray-900">{{ __('onboarding.steps.first_campaign.title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('onboarding.completed_label') }}</p>
            </div>
        </div>
    </div>

    {{-- Next Steps --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            {{ __('onboarding.next_steps') }}
        </h2>

        <div class="space-y-4">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}"
               class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition">
                    <i class="fas fa-chart-line text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">{{ __('onboarding.go_to_dashboard') }}</h3>
                    <p class="text-sm text-gray-600">{{ __('onboarding.dashboard_description') }}</p>
                </div>
                <i class="fas fa-chevron-{{ app()->isLocale('ar') ? 'left' : 'right' }} text-gray-400"></i>
            </a>

            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}"
               class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition">
                    <i class="fas fa-bullhorn text-purple-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">{{ __('onboarding.view_campaigns') }}</h3>
                    <p class="text-sm text-gray-600">{{ __('onboarding.campaigns_description') }}</p>
                </div>
                <i class="fas fa-chevron-{{ app()->isLocale('ar') ? 'left' : 'right' }} text-gray-400"></i>
            </a>

            <a href="{{ route('orgs.analytics.index', $currentOrg) }}"
               class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition group">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition">
                    <i class="fas fa-analytics text-green-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-medium text-gray-900">{{ __('onboarding.explore_analytics') }}</h3>
                    <p class="text-sm text-gray-600">{{ __('onboarding.analytics_description') }}</p>
                </div>
                <i class="fas fa-chevron-{{ app()->isLocale('ar') ? 'left' : 'right' }} text-gray-400"></i>
            </a>
        </div>
    </div>
</div>
@endsection

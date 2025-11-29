@extends('layouts.admin')

@section('title', __('subscription.subscription_status'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('subscription.subscription_status') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('subscription.subscription_status') }}</h1>
    </div>

    {{-- Current Plan Card --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('subscription.current_plan') }}</h2>
            <span class="px-3 py-1 text-sm font-medium rounded-full
                @if($subscription['status'] === 'active') bg-green-100 text-green-800
                @elseif($subscription['status'] === 'canceled') bg-red-100 text-red-800
                @else bg-yellow-100 text-yellow-800 @endif">
                {{ ucfirst($subscription['status']) }}
            </span>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ ucfirst($subscription['plan']) }}
                </h3>
                <p class="text-gray-600">
                    {{ __('subscription.plan_' . $subscription['plan'] . '_description', ['default' => '']) }}
                </p>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">{{ __('subscription.next_billing_date') }}:</span>
                    <span class="font-medium">{{ $subscription['current_period_end'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">{{ __('subscription.ai_rate_limit') }}:</span>
                    <span class="font-medium">{{ $subscription['ai_rate_limit'] }} {{ __('subscription.requests_per_minute') }}</span>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-200 flex flex-wrap gap-3">
            <a href="{{ route('subscription.plans') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                <i class="fas fa-arrow-up me-2"></i>
                {{ __('subscription.view_plans') }}
            </a>
            <a href="{{ route('subscription.payment') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition">
                <i class="fas fa-credit-card me-2"></i>
                {{ __('subscription.manage_billing') }}
            </a>
        </div>
    </div>

    {{-- Usage Summary --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('subscription.usage_summary') }}</h2>

        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-robot text-blue-600"></i>
                    </div>
                    <span class="font-medium text-gray-900">{{ __('subscription.ai_requests') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900">0 / {{ $subscription['ai_rate_limit'] * 60 }}</p>
                <p class="text-sm text-gray-500">{{ __('subscription.per_hour') }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bullhorn text-green-600"></i>
                    </div>
                    <span class="font-medium text-gray-900">{{ __('subscription.active_campaigns') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900">0</p>
                <p class="text-sm text-gray-500">{{ __('subscription.campaigns_running') }}</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                    <span class="font-medium text-gray-900">{{ __('subscription.team_members') }}</span>
                </div>
                <p class="text-2xl font-bold text-gray-900">1</p>
                <p class="text-sm text-gray-500">{{ __('subscription.members_active') }}</p>
            </div>
        </div>
    </div>

    {{-- Cancel Subscription --}}
    @if($subscription['status'] === 'active' && $subscription['plan'] !== 'free')
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('subscription.cancel_subscription') }}</h2>
        <p class="text-gray-600 mb-4">{{ __('subscription.cancel_description') }}</p>

        <form action="{{ route('subscription.cancel') }}" method="POST"
              onsubmit="return confirm('{{ __('subscription.cancel_confirm') }}')">
            @csrf
            <label class="flex items-center gap-2 mb-4">
                <input type="checkbox" name="confirm_cancellation" class="rounded border-gray-300 text-red-600 focus:ring-red-500" required>
                <span class="text-sm text-gray-700">{{ __('subscription.confirm_cancellation_checkbox') }}</span>
            </label>
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                {{ __('subscription.cancel_subscription_button') }}
            </button>
        </form>
    </div>
    @endif
</div>
@endsection

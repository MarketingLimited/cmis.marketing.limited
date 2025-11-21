@extends('layouts.app')

@section('title', __('subscription.upgrade_to_pro'))

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            {{ __('subscription.unlock_full_potential') }}
        </h1>
        <p class="text-xl text-gray-600">
            {{ __('subscription.upgrade_description') }}
        </p>
    </div>

    {{-- Comparison Table --}}
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="grid md:grid-cols-2 divide-x divide-gray-200">
            {{-- Current Plan (Free) --}}
            <div class="p-8">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('subscription.current_plan') }}</h3>
                    <div class="text-3xl font-bold text-gray-900">{{ __('subscription.free') }}</div>
                    <div class="text-gray-600">$0/{{ __('subscription.month') }}</div>
                </div>

                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-600">5 {{ __('subscription.ai_requests_per_day') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-600">3 {{ __('subscription.active_campaigns') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-600">{{ __('subscription.basic_analytics') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-300 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-400 line-through">{{ __('subscription.advanced_analytics') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-gray-300 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-400 line-through">{{ __('subscription.team_collaboration') }}</span>
                    </li>
                </ul>
            </div>

            {{-- Pro Plan --}}
            <div class="p-8 bg-gradient-to-br from-blue-50 to-purple-50">
                <div class="text-center mb-6">
                    <div class="inline-block px-3 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full mb-2">
                        {{ __('subscription.recommended') }}
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('subscription.upgrade_to') }}</h3>
                    <div class="text-3xl font-bold text-blue-600">{{ __('subscription.pro') }}</div>
                    <div class="text-gray-600">$49/{{ __('subscription.month') }}</div>
                </div>

                <ul class="space-y-4">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-900 font-medium">50 {{ __('subscription.ai_requests_per_day') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-900 font-medium">{{ __('subscription.unlimited_campaigns') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-900 font-medium">{{ __('subscription.advanced_analytics') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-900 font-medium">5 {{ __('subscription.team_members') }}</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-gray-900 font-medium">{{ __('subscription.priority_support') }}</span>
                    </li>
                </ul>

                <div class="mt-8">
                    <form action="{{ route('subscription.upgrade.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="plan" value="pro">
                        <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-semibold text-lg shadow-lg hover:shadow-xl transition-all">
                            {{ __('subscription.upgrade_now') }}
                        </button>
                    </form>
                    <p class="text-xs text-center text-gray-600 mt-3">
                        {{ __('subscription.cancel_anytime') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Benefits Section --}}
    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">
            {{ __('subscription.why_upgrade') }}
        </h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.benefit_ai_title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('subscription.benefit_ai_description') }}</p>
            </div>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 mb-4">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.benefit_analytics_title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('subscription.benefit_analytics_description') }}</p>
            </div>
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.benefit_team_title') }}</h3>
                <p class="text-sm text-gray-600">{{ __('subscription.benefit_team_description') }}</p>
            </div>
        </div>
    </div>

    {{-- Money Back Guarantee --}}
    <div class="text-center bg-gray-50 rounded-lg p-6">
        <svg class="w-12 h-12 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('subscription.money_back_guarantee') }}</h3>
        <p class="text-gray-600">{{ __('subscription.money_back_description') }}</p>
    </div>
</div>
@endsection

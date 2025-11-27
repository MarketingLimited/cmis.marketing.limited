@extends('layouts.admin')

@section('title', __('subscription.pricing_plans'))

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
            <span class="text-gray-900 font-medium">{{ __('Subscription Plans') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
            {{ __('subscription.choose_your_plan') }}
        </h1>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
            {{ __('subscription.plan_description') }}
        </p>
    </div>

    {{-- Pricing Cards --}}
    <div class="grid md:grid-cols-3 gap-8 mb-12">
        {{-- Free Plan --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-gray-200">
            <div class="p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('subscription.free') }}</h3>
                <p class="text-gray-600 mb-6">{{ __('subscription.free_description') }}</p>

                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">$0</span>
                    <span class="text-gray-600">/{{ __('subscription.month') }}</span>
                </div>

                <a href="#" class="block w-full text-center px-4 py-2 border-2 border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                    {{ __('subscription.current_plan') }}
                </a>

                <div class="mt-8">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">{{ __('subscription.includes') }}:</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.free.ai_requests', ['count' => 5]) }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.free.campaigns', ['count' => 3]) }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.basic_analytics') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.meta_integration') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Pro Plan --}}
        <div class="bg-white rounded-lg shadow-xl overflow-hidden border-2 border-blue-500 relative">
            <div class="absolute top-0 right-0 bg-blue-500 text-white px-3 py-1 text-sm font-semibold rounded-bl-lg">
                {{ __('subscription.popular') }}
            </div>
            <div class="p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('subscription.pro') }}</h3>
                <p class="text-gray-600 mb-6">{{ __('subscription.pro_description') }}</p>

                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">$49</span>
                    <span class="text-gray-600">/{{ __('subscription.month') }}</span>
                </div>

                <a href="{{ route('subscription.upgrade') }}" class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                    {{ __('subscription.upgrade_now') }}
                </a>

                <div class="mt-8">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">{{ __('subscription.everything_in_free_plus') }}:</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.pro.ai_requests', ['count' => 50]) }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.unlimited_campaigns') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.advanced_analytics') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.team_members', ['count' => 5]) }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.priority_support') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Enterprise Plan --}}
        <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-gray-200">
            <div class="p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ __('subscription.enterprise') }}</h3>
                <p class="text-gray-600 mb-6">{{ __('subscription.enterprise_description') }}</p>

                <div class="mb-6">
                    <span class="text-4xl font-bold text-gray-900">{{ __('subscription.custom') }}</span>
                </div>

                <a href="mailto:sales@cmis.marketing" class="block w-full text-center px-4 py-2 border-2 border-gray-900 rounded-md text-gray-900 hover:bg-gray-900 hover:text-white font-medium transition-colors">
                    {{ __('subscription.contact_sales') }}
                </a>

                <div class="mt-8">
                    <h4 class="text-sm font-semibold text-gray-900 mb-4">{{ __('subscription.everything_in_pro_plus') }}:</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.unlimited_ai') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.unlimited_team') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.dedicated_support') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.custom_integrations') }}</span>
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-purple-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-gray-600">{{ __('subscription.features.sla_guarantee') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ Section --}}
    <div class="max-w-3xl mx-auto mt-16">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">
            {{ __('subscription.faq') }}
        </h2>
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.faq_change_plan') }}</h3>
                <p class="text-gray-600">{{ __('subscription.faq_change_plan_answer') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.faq_cancel') }}</h3>
                <p class="text-gray-600">{{ __('subscription.faq_cancel_answer') }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-semibold text-gray-900 mb-2">{{ __('subscription.faq_payment') }}</h3>
                <p class="text-gray-600">{{ __('subscription.faq_payment_answer') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('marketing.layouts.app')

@section('title', __('marketing.pricing.title'))
@section('meta_description', __('marketing.pricing.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-24 overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-20 {{ $isRtl ? 'right-20' : 'left-20' }} w-72 h-72 bg-green-600/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-20 {{ $isRtl ? 'left-20' : 'right-20' }} w-96 h-96 bg-red-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1 bg-green-600/20 text-green-400 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-tags me-1"></i> {{ __('marketing.pricing.badge') ?? __('marketing.pricing.headline') }}
        </span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">{{ __('marketing.pricing.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto mb-8">{{ __('marketing.pricing.subheadline') }}</p>

        <!-- Billing Toggle with Animation -->
        <div x-data="{ annual: true }" class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full p-1.5 border border-white/20">
            <button @click="annual = false" :class="!annual ? 'bg-white text-slate-900 shadow-lg' : 'text-white hover:bg-white/10'" class="px-6 py-2.5 rounded-full font-medium transition-all duration-300">
                {{ __('marketing.pricing.monthly') }}
            </button>
            <button @click="annual = true" :class="annual ? 'bg-white text-slate-900 shadow-lg' : 'text-white hover:bg-white/10'" class="px-6 py-2.5 rounded-full font-medium transition-all duration-300 flex items-center gap-2">
                {{ __('marketing.pricing.annual') }}
                <span class="text-xs bg-gradient-to-r from-green-500 to-emerald-500 text-white px-2.5 py-1 rounded-full font-bold animate-pulse">{{ __('marketing.pricing.save_20') }}</span>
            </button>
        </div>
    </div>
</section>

<!-- Pricing Cards -->
<section class="py-20 bg-white dark:bg-slate-900 -mt-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ annual: true }">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($plans ?? [] as $plan)
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl border-2 {{ $plan->is_featured ? 'border-red-600 shadow-xl shadow-red-600/10' : 'border-slate-200 dark:border-slate-700' }} overflow-hidden">
                    @if($plan->is_featured)
                        <div class="absolute top-0 inset-x-0 bg-red-600 text-white text-center py-1 text-sm font-medium">
                            {{ __('marketing.pricing.most_popular') }}
                        </div>
                    @endif

                    <div class="p-8 {{ $plan->is_featured ? 'pt-12' : '' }}">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $plan->name }}</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-4xl font-bold text-slate-900 dark:text-white">
                                $<span x-text="annual ? '{{ number_format($plan->price_yearly / 12, 0) }}' : '{{ number_format($plan->price_monthly, 0) }}'"></span>
                            </span>
                            <span class="text-slate-500">/{{ __('marketing.pricing.per_month') }}</span>
                            <template x-if="annual">
                                <p class="text-sm text-green-600 mt-1">{{ __('marketing.pricing.billed_annually') }}</p>
                            </template>
                        </div>

                        <a href="{{ route('marketing.demo') }}" class="block w-full text-center py-3 rounded-lg font-semibold transition {{ $plan->is_featured ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                            {{ __('marketing.pricing.get_started') }}
                        </a>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 p-8">
                        <p class="font-semibold text-slate-900 dark:text-white mb-4">{{ __('marketing.pricing.includes') }}:</p>
                        <ul class="space-y-3">
                            @foreach($plan->features ?? [] as $feature)
                                <li class="flex items-start gap-3">
                                    <i class="fas fa-check text-green-500 mt-1"></i>
                                    <span class="text-slate-600 dark:text-slate-400">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @empty
                <!-- Default Plans -->
                @foreach([
                    [
                        'name' => __('marketing.pricing.starter'),
                        'description' => __('marketing.pricing.starter_desc'),
                        'monthly' => 49,
                        'yearly' => 470,
                        'featured' => false,
                        'features' => [
                            __('marketing.pricing.feature_users', ['count' => 2]),
                            __('marketing.pricing.feature_platforms', ['count' => 3]),
                            __('marketing.pricing.feature_posts', ['count' => 100]),
                            __('marketing.pricing.feature_analytics'),
                            __('marketing.pricing.feature_support'),
                        ]
                    ],
                    [
                        'name' => __('marketing.pricing.professional'),
                        'description' => __('marketing.pricing.professional_desc'),
                        'monthly' => 99,
                        'yearly' => 950,
                        'featured' => true,
                        'features' => [
                            __('marketing.pricing.feature_users', ['count' => 10]),
                            __('marketing.pricing.feature_platforms', ['count' => 6]),
                            __('marketing.pricing.feature_posts_unlimited'),
                            __('marketing.pricing.feature_ai'),
                            __('marketing.pricing.feature_reporting'),
                            __('marketing.pricing.feature_priority_support'),
                        ]
                    ],
                    [
                        'name' => __('marketing.pricing.enterprise'),
                        'description' => __('marketing.pricing.enterprise_desc'),
                        'monthly' => 249,
                        'yearly' => 2390,
                        'featured' => false,
                        'features' => [
                            __('marketing.pricing.feature_users_unlimited'),
                            __('marketing.pricing.feature_platforms_all'),
                            __('marketing.pricing.feature_api'),
                            __('marketing.pricing.feature_white_label'),
                            __('marketing.pricing.feature_sso'),
                            __('marketing.pricing.feature_dedicated_support'),
                        ]
                    ],
                ] as $plan)
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl border-2 {{ $plan['featured'] ? 'border-red-600 shadow-xl shadow-red-600/10' : 'border-slate-200 dark:border-slate-700' }} overflow-hidden">
                        @if($plan['featured'])
                            <div class="absolute top-0 inset-x-0 bg-red-600 text-white text-center py-1 text-sm font-medium">
                                {{ __('marketing.pricing.most_popular') }}
                            </div>
                        @endif

                        <div class="p-8 {{ $plan['featured'] ? 'pt-12' : '' }}">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $plan['name'] }}</h3>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $plan['description'] }}</p>

                            <div class="mb-6">
                                <span class="text-4xl font-bold text-slate-900 dark:text-white">
                                    $<span x-text="annual ? '{{ number_format($plan['yearly'] / 12, 0) }}' : '{{ $plan['monthly'] }}'"></span>
                                </span>
                                <span class="text-slate-500">/{{ __('marketing.pricing.per_month') }}</span>
                                <template x-if="annual">
                                    <p class="text-sm text-green-600 mt-1">{{ __('marketing.pricing.billed_annually') }}</p>
                                </template>
                            </div>

                            <a href="{{ route('marketing.demo') }}" class="block w-full text-center py-3 rounded-lg font-semibold transition {{ $plan['featured'] ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                                {{ __('marketing.pricing.get_started') }}
                            </a>
                        </div>

                        <div class="border-t border-slate-200 dark:border-slate-700 p-8">
                            <p class="font-semibold text-slate-900 dark:text-white mb-4">{{ __('marketing.pricing.includes') }}:</p>
                            <ul class="space-y-3">
                                @foreach($plan['features'] as $feature)
                                    <li class="flex items-start gap-3">
                                        <i class="fas fa-check text-green-500 mt-1"></i>
                                        <span class="text-slate-600 dark:text-slate-400">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.pricing.faq_title') }}</h2>
            <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.pricing.faq_subtitle') }}</p>
        </div>

        <div class="space-y-4" x-data="{ open: null }">
            @foreach([
                ['q' => __('marketing.pricing.faq_1_q'), 'a' => __('marketing.pricing.faq_1_a')],
                ['q' => __('marketing.pricing.faq_2_q'), 'a' => __('marketing.pricing.faq_2_a')],
                ['q' => __('marketing.pricing.faq_3_q'), 'a' => __('marketing.pricing.faq_3_a')],
                ['q' => __('marketing.pricing.faq_4_q'), 'a' => __('marketing.pricing.faq_4_a')],
            ] as $index => $faq)
                <div class="bg-white dark:bg-slate-700 rounded-xl overflow-hidden">
                    <button @click="open = open === {{ $index }} ? null : {{ $index }}" class="w-full flex items-center justify-between p-6 text-start">
                        <span class="font-semibold text-slate-900 dark:text-white">{{ $faq['q'] }}</span>
                        <i class="fas fa-chevron-down text-slate-400 transition-transform" :class="open === {{ $index }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $index }}" x-collapse class="px-6 pb-6">
                        <p class="text-slate-600 dark:text-slate-400">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('marketing.faq') }}" class="text-red-600 hover:text-red-700 font-semibold">
                {{ __('marketing.pricing.view_all_faq') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} ms-1"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('marketing.pricing.cta_title') }}</h2>
        <p class="text-xl text-white/80 mb-8">{{ __('marketing.pricing.cta_subtitle') }}</p>
        <a href="{{ route('marketing.contact') }}" class="inline-block px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
            {{ __('marketing.pricing.contact_sales') }}
        </a>
    </div>
</section>
@endsection

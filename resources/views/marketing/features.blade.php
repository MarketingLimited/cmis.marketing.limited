@extends('marketing.layouts.app')

@section('title', __('marketing.features.title'))
@section('meta_description', __('marketing.features.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __('marketing.features.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.features.subheadline') }}</p>
    </div>
</section>

<!-- Features by Category -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @forelse($categories ?? [] as $category)
            <div class="mb-20">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-purple-600 rounded-xl flex items-center justify-center">
                        <i class="{{ $category->icon ?? 'fas fa-folder' }} text-xl text-white"></i>
                    </div>
                    <h2 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">{{ $category->name }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($category->features as $feature)
                        <div class="group bg-slate-50 dark:bg-slate-800 rounded-xl p-6 hover:shadow-xl transition-all duration-300 border border-slate-200 dark:border-slate-700">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600 dark:text-red-400">
                                    <i class="{{ $feature->icon ?? 'fas fa-check' }}"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900 dark:text-white mb-2">{{ $feature->title }}</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $feature->description }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <!-- Default Features Grid -->
            @foreach([
                [
                    'name' => __('marketing.features.category_analytics'),
                    'icon' => 'fas fa-chart-pie',
                    'features' => [
                        ['icon' => 'fas fa-chart-line', 'title' => __('marketing.features.realtime_analytics'), 'desc' => __('marketing.features.realtime_analytics_desc')],
                        ['icon' => 'fas fa-project-diagram', 'title' => __('marketing.features.cross_platform'), 'desc' => __('marketing.features.cross_platform_desc')],
                        ['icon' => 'fas fa-file-export', 'title' => __('marketing.features.custom_reports'), 'desc' => __('marketing.features.custom_reports_desc')],
                    ]
                ],
                [
                    'name' => __('marketing.features.category_campaigns'),
                    'icon' => 'fas fa-bullhorn',
                    'features' => [
                        ['icon' => 'fas fa-magic', 'title' => __('marketing.features.ai_optimization'), 'desc' => __('marketing.features.ai_optimization_desc')],
                        ['icon' => 'fas fa-bullseye', 'title' => __('marketing.features.smart_targeting'), 'desc' => __('marketing.features.smart_targeting_desc')],
                        ['icon' => 'fas fa-dollar-sign', 'title' => __('marketing.features.budget_management'), 'desc' => __('marketing.features.budget_management_desc')],
                    ]
                ],
                [
                    'name' => __('marketing.features.category_social'),
                    'icon' => 'fas fa-share-alt',
                    'features' => [
                        ['icon' => 'fas fa-calendar-alt', 'title' => __('marketing.features.content_calendar'), 'desc' => __('marketing.features.content_calendar_desc')],
                        ['icon' => 'fas fa-clock', 'title' => __('marketing.features.auto_scheduling'), 'desc' => __('marketing.features.auto_scheduling_desc')],
                        ['icon' => 'fas fa-comments', 'title' => __('marketing.features.engagement_tools'), 'desc' => __('marketing.features.engagement_tools_desc')],
                    ]
                ],
                [
                    'name' => __('marketing.features.category_ai'),
                    'icon' => 'fas fa-brain',
                    'features' => [
                        ['icon' => 'fas fa-lightbulb', 'title' => __('marketing.features.content_suggestions'), 'desc' => __('marketing.features.content_suggestions_desc')],
                        ['icon' => 'fas fa-chart-bar', 'title' => __('marketing.features.predictive_analytics'), 'desc' => __('marketing.features.predictive_analytics_desc')],
                        ['icon' => 'fas fa-search', 'title' => __('marketing.features.semantic_search'), 'desc' => __('marketing.features.semantic_search_desc')],
                    ]
                ],
            ] as $cat)
                <div class="mb-20">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-purple-600 rounded-xl flex items-center justify-center">
                            <i class="{{ $cat['icon'] }} text-xl text-white"></i>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">{{ $cat['name'] }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($cat['features'] as $f)
                            <div class="group bg-slate-50 dark:bg-slate-800 rounded-xl p-6 hover:shadow-xl transition-all duration-300 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600 dark:text-red-400">
                                        <i class="{{ $f['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-slate-900 dark:text-white mb-2">{{ $f['title'] }}</h3>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $f['desc'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endforelse
    </div>
</section>

<!-- Platform Integrations -->
<section class="py-20 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.features.integrations_title') }}</h2>
            <p class="text-xl text-slate-600 dark:text-slate-400">{{ __('marketing.features.integrations_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach([
                ['name' => 'Meta', 'icon' => 'fab fa-facebook', 'color' => 'text-blue-600'],
                ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => 'text-pink-600'],
                ['name' => 'Google', 'icon' => 'fab fa-google', 'color' => 'text-red-500'],
                ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'color' => 'text-slate-900 dark:text-white'],
                ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => 'text-blue-700'],
                ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => 'text-sky-500'],
            ] as $platform)
                <div class="bg-white dark:bg-slate-700 rounded-xl p-6 text-center shadow-sm hover:shadow-lg transition">
                    <i class="{{ $platform['icon'] }} text-4xl {{ $platform['color'] }} mb-3"></i>
                    <p class="font-semibold text-slate-900 dark:text-white">{{ $platform['name'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('marketing.features.cta_title') }}</h2>
        <p class="text-xl text-white/80 mb-8">{{ __('marketing.features.cta_subtitle') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
                {{ __('marketing.nav.demo') }}
            </a>
            <a href="{{ route('marketing.pricing') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition">
                {{ __('marketing.nav.pricing') }}
            </a>
        </div>
    </div>
</section>
@endsection

@extends('marketing.layouts.app')

@section('title', __('marketing.features.title'))
@section('meta_description', __('marketing.features.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-24 overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-20 {{ $isRtl ? 'right-20' : 'left-20' }} w-72 h-72 bg-red-600/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-20 {{ $isRtl ? 'left-20' : 'right-20' }} w-96 h-96 bg-purple-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1 bg-red-600/20 text-red-400 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-sparkles me-1"></i> {{ __('marketing.features.badge') ?? __('marketing.features.headline') }}
        </span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">{{ __('marketing.features.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto mb-8">{{ __('marketing.features.subheadline') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all transform hover:scale-105 shadow-lg shadow-red-600/30">
                {{ __('marketing.nav.demo') }}
            </a>
            <a href="{{ route('marketing.pricing') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition backdrop-blur-sm">
                {{ __('marketing.nav.pricing') }}
            </a>
        </div>
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
<section class="py-20 bg-slate-100 dark:bg-slate-800 relative overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute top-0 {{ $isRtl ? 'left-0' : 'right-0' }} w-96 h-96 bg-red-600 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 {{ $isRtl ? 'right-0' : 'left-0' }} w-96 h-96 bg-purple-600 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <span class="text-red-600 font-semibold text-sm uppercase tracking-wider mb-2 block">{{ __('marketing.features.integrations_badge') ?? 'Integrations' }}</span>
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.features.integrations_title') }}</h2>
            <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">{{ __('marketing.features.integrations_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
            @foreach([
                ['name' => 'Meta', 'icon' => 'fab fa-facebook', 'color' => 'from-blue-500 to-blue-600', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => 'from-pink-500 to-purple-600', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                ['name' => 'Google', 'icon' => 'fab fa-google', 'color' => 'from-red-500 to-yellow-500', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
                ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'color' => 'from-slate-700 to-slate-900', 'bg' => 'bg-slate-100 dark:bg-slate-700/50'],
                ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => 'from-blue-600 to-blue-700', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => 'from-slate-700 to-slate-900', 'bg' => 'bg-slate-100 dark:bg-slate-700/50'],
            ] as $platform)
                <div class="group">
                    <div class="bg-white dark:bg-slate-700 rounded-2xl p-6 text-center shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-slate-100 dark:border-slate-600">
                        <div class="w-16 h-16 mx-auto {{ $platform['bg'] }} rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="{{ $platform['icon'] }} text-3xl text-transparent bg-clip-text bg-gradient-to-br {{ $platform['color'] }}"></i>
                        </div>
                        <p class="font-bold text-slate-900 dark:text-white">{{ $platform['name'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ __('marketing.features.connected') ?? 'Connected' }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Additional platforms text -->
        <div class="text-center mt-12">
            <p class="text-slate-600 dark:text-slate-400">
                <i class="fas fa-plus-circle me-2 text-red-600"></i>
                {{ __('marketing.features.more_integrations') ?? 'And many more integrations coming soon...' }}
            </p>
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

@extends('marketing.layouts.app')

@section('title', __('marketing.home.title'))
@section('meta_description', __('marketing.home.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white overflow-hidden">
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-0 {{ $isRtl ? 'left-0' : 'right-0' }} w-1/2 h-full bg-gradient-to-{{ $isRtl ? 'r' : 'l' }} from-red-600/20 to-transparent"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
        @if(isset($heroSlides) && $heroSlides->isNotEmpty())
            <div x-data="{ activeSlide: 0 }" class="text-center">
                @foreach($heroSlides as $index => $slide)
                    <div x-show="activeSlide === {{ $index }}" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                            {{ $slide->headline }}
                        </h1>
                        <p class="text-xl md:text-2xl text-slate-300 mb-8 max-w-3xl mx-auto">
                            {{ $slide->subheadline }}
                        </p>
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ $slide->cta_url }}" class="px-8 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all transform hover:scale-105 shadow-lg shadow-red-600/30">
                                {{ $slide->cta_text }}
                            </a>
                            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition backdrop-blur-sm">
                                {{ __('marketing.home.watch_demo') }}
                            </a>
                        </div>
                    </div>
                @endforeach

                @if($heroSlides->count() > 1)
                    <div class="flex justify-center gap-2 mt-8">
                        @foreach($heroSlides as $index => $slide)
                            <button @click="activeSlide = {{ $index }}" :class="activeSlide === {{ $index }} ? 'bg-red-600' : 'bg-white/30'" class="w-3 h-3 rounded-full transition"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    {{ __('marketing.home.default_headline') }}
                </h1>
                <p class="text-xl md:text-2xl text-slate-300 mb-8 max-w-3xl mx-auto">
                    {{ __('marketing.home.default_subheadline') }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all transform hover:scale-105 shadow-lg shadow-red-600/30">
                        {{ __('marketing.home.get_started') }}
                    </a>
                    <a href="{{ route('marketing.features') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition backdrop-blur-sm">
                        {{ __('marketing.home.learn_more') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Stats Bar -->
    <div class="relative border-t border-white/10 bg-white/5 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl md:text-4xl font-bold text-red-500">500+</div>
                    <div class="text-slate-400 mt-1">{{ __('marketing.home.stats.clients') }}</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-bold text-red-500">10M+</div>
                    <div class="text-slate-400 mt-1">{{ __('marketing.home.stats.campaigns') }}</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-bold text-red-500">6</div>
                    <div class="text-slate-400 mt-1">{{ __('marketing.home.stats.platforms') }}</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-bold text-red-500">99.9%</div>
                    <div class="text-slate-400 mt-1">{{ __('marketing.home.stats.uptime') }}</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Overview -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                {{ __('marketing.home.features_title') }}
            </h2>
            <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
                {{ __('marketing.home.features_subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($features ?? [] as $feature)
                <div class="group p-6 bg-slate-50 dark:bg-slate-800 rounded-xl hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-600 to-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="{{ $feature->icon ?? 'fas fa-star' }} text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">{{ $feature->title }}</h3>
                    <p class="text-slate-600 dark:text-slate-400">{{ $feature->description }}</p>
                </div>
            @empty
                @foreach([
                    ['icon' => 'fas fa-chart-line', 'title' => __('marketing.home.feature_analytics'), 'desc' => __('marketing.home.feature_analytics_desc')],
                    ['icon' => 'fas fa-bullseye', 'title' => __('marketing.home.feature_targeting'), 'desc' => __('marketing.home.feature_targeting_desc')],
                    ['icon' => 'fas fa-robot', 'title' => __('marketing.home.feature_ai'), 'desc' => __('marketing.home.feature_ai_desc')],
                    ['icon' => 'fas fa-share-alt', 'title' => __('marketing.home.feature_social'), 'desc' => __('marketing.home.feature_social_desc')],
                    ['icon' => 'fas fa-clock', 'title' => __('marketing.home.feature_scheduling'), 'desc' => __('marketing.home.feature_scheduling_desc')],
                    ['icon' => 'fas fa-shield-alt', 'title' => __('marketing.home.feature_security'), 'desc' => __('marketing.home.feature_security_desc')],
                ] as $f)
                    <div class="group p-6 bg-slate-50 dark:bg-slate-800 rounded-xl hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div class="w-14 h-14 bg-gradient-to-br from-red-600 to-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                            <i class="{{ $f['icon'] }} text-2xl text-white"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">{{ $f['title'] }}</h3>
                        <p class="text-slate-600 dark:text-slate-400">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            @endforelse
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-semibold">
                {{ __('marketing.home.view_all_features') }}
                <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
            </a>
        </div>
    </div>
</section>

<!-- Platform Logos -->
<section class="py-16 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-slate-600 dark:text-slate-400 mb-8">{{ __('marketing.home.platforms_title') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-8 md:gap-16">
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-facebook"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-instagram"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-google"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-tiktok"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-linkedin"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-twitter"></i></div>
            <div class="text-4xl text-slate-400 dark:text-slate-500"><i class="fab fa-snapchat"></i></div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                {{ __('marketing.home.testimonials_title') }}
            </h2>
            <p class="text-xl text-slate-600 dark:text-slate-400">
                {{ __('marketing.home.testimonials_subtitle') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($testimonials ?? [] as $testimonial)
                <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-1 text-yellow-500 mb-4">
                        @for($i = 0; $i < ($testimonial->rating ?? 5); $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div>
                    <p class="text-slate-700 dark:text-slate-300 mb-6 italic">"{{ $testimonial->quote }}"</p>
                    <div class="flex items-center gap-3">
                        @if($testimonial->image_url)
                            <img src="{{ $testimonial->image_url }}" alt="{{ $testimonial->author }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center text-white font-bold">
                                {{ substr($testimonial->author, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ $testimonial->author }}</div>
                            <div class="text-sm text-slate-500">{{ $testimonial->role }}, {{ $testimonial->company }}</div>
                        </div>
                    </div>
                </div>
            @empty
                @foreach([
                    ['quote' => __('marketing.home.testimonial_1_quote'), 'author' => __('marketing.home.testimonial_1_author'), 'role' => __('marketing.home.testimonial_1_role'), 'company' => __('marketing.home.testimonial_1_company')],
                    ['quote' => __('marketing.home.testimonial_2_quote'), 'author' => __('marketing.home.testimonial_2_author'), 'role' => __('marketing.home.testimonial_2_role'), 'company' => __('marketing.home.testimonial_2_company')],
                    ['quote' => __('marketing.home.testimonial_3_quote'), 'author' => __('marketing.home.testimonial_3_author'), 'role' => __('marketing.home.testimonial_3_role'), 'company' => __('marketing.home.testimonial_3_company')],
                ] as $t)
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6">
                        <div class="flex items-center gap-1 text-yellow-500 mb-4">
                            @for($i = 0; $i < 5; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 mb-6 italic">"{{ $t['quote'] }}"</p>
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center text-white font-bold">
                                {{ mb_substr($t['author'], 0, 1) }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $t['author'] }}</div>
                                <div class="text-sm text-slate-500">{{ $t['role'] }}, {{ $t['company'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('marketing.home.cta_title') }}</h2>
        <p class="text-xl text-white/80 mb-8">{{ __('marketing.home.cta_subtitle') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition-all transform hover:scale-105 shadow-lg">
                {{ __('marketing.home.cta_demo') }}
            </a>
            <a href="{{ route('marketing.contact') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition backdrop-blur-sm">
                {{ __('marketing.home.cta_contact') }}
            </a>
        </div>
    </div>
</section>

<!-- Partners -->
@if(isset($partners) && $partners->isNotEmpty())
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-slate-600 dark:text-slate-400 mb-8">{{ __('marketing.home.partners_title') }}</p>
        <div class="flex flex-wrap items-center justify-center gap-8 md:gap-12">
            @foreach($partners as $partner)
                <a href="{{ $partner->website_url }}" target="_blank" rel="noopener" class="grayscale hover:grayscale-0 transition">
                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="h-12 object-contain">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

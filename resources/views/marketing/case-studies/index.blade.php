@extends('marketing.layouts.app')

@section('title', __('marketing.case_studies.title'))
@section('meta_description', __('marketing.case_studies.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-24 overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-20 {{ $isRtl ? 'right-20' : 'left-20' }} w-72 h-72 bg-amber-600/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-20 {{ $isRtl ? 'left-20' : 'right-20' }} w-96 h-96 bg-rose-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1 bg-amber-600/20 text-amber-400 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-trophy me-1"></i> {{ __('marketing.case_studies.badge') ?? __('marketing.case_studies.headline') }}
        </span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">{{ __('marketing.case_studies.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.case_studies.subheadline') }}</p>
    </div>
</section>

<!-- Case Studies Grid -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            @forelse($caseStudies ?? [] as $study)
                <article class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <div class="aspect-video overflow-hidden">
                        @if($study->image_url)
                            <img src="{{ $study->image_url }}" alt="{{ $study->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-building text-6xl text-white/50"></i>
                            </div>
                        @endif
                    </div>
                    <div class="p-8">
                        <div class="flex items-center gap-2 text-sm text-slate-500 mb-3">
                            @if($study->industry)
                                <span class="px-2 py-1 bg-slate-200 dark:bg-slate-700 rounded text-xs">{{ $study->industry }}</span>
                            @endif
                            <span>{{ $study->client_name }}</span>
                        </div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">
                            <a href="{{ route('marketing.case-studies.show', $study->slug) }}" class="hover:text-red-600 transition">
                                {{ $study->title }}
                            </a>
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $study->excerpt }}</p>

                        <!-- Metrics -->
                        @if($study->metrics)
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                @foreach(array_slice($study->metrics, 0, 3) as $metric)
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ $metric['value'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $metric['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <a href="{{ route('marketing.case-studies.show', $study->slug) }}" class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-semibold">
                            {{ __('marketing.case_studies.read_more') }}
                            <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                        </a>
                    </div>
                </article>
            @empty
                <!-- Default Case Studies -->
                @foreach([
                    ['title' => __('marketing.case_studies.example_1_title'), 'client' => __('marketing.case_studies.example_1_client'), 'industry' => __('marketing.case_studies.example_1_industry'), 'desc' => __('marketing.case_studies.example_1_desc'), 'metrics' => [['value' => '+250%', 'label' => __('marketing.case_studies.metric_roas')], ['value' => '-40%', 'label' => __('marketing.case_studies.metric_cpa')], ['value' => '+180%', 'label' => __('marketing.case_studies.metric_conversions')]]],
                    ['title' => __('marketing.case_studies.example_2_title'), 'client' => __('marketing.case_studies.example_2_client'), 'industry' => __('marketing.case_studies.example_2_industry'), 'desc' => __('marketing.case_studies.example_2_desc'), 'metrics' => [['value' => '10x', 'label' => __('marketing.case_studies.metric_reach')], ['value' => '+300%', 'label' => __('marketing.case_studies.metric_engagement')], ['value' => '50K+', 'label' => __('marketing.case_studies.metric_followers')]]],
                    ['title' => __('marketing.case_studies.example_3_title'), 'client' => __('marketing.case_studies.example_3_client'), 'industry' => __('marketing.case_studies.example_3_industry'), 'desc' => __('marketing.case_studies.example_3_desc'), 'metrics' => [['value' => '+85%', 'label' => __('marketing.case_studies.metric_leads')], ['value' => '-35%', 'label' => __('marketing.case_studies.metric_cost')], ['value' => '2.5x', 'label' => __('marketing.case_studies.metric_roi')]]],
                    ['title' => __('marketing.case_studies.example_4_title'), 'client' => __('marketing.case_studies.example_4_client'), 'industry' => __('marketing.case_studies.example_4_industry'), 'desc' => __('marketing.case_studies.example_4_desc'), 'metrics' => [['value' => '+400%', 'label' => __('marketing.case_studies.metric_sales')], ['value' => '15M', 'label' => __('marketing.case_studies.metric_impressions')], ['value' => '8.5%', 'label' => __('marketing.case_studies.metric_ctr')]]],
                ] as $study)
                    <article class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <div class="aspect-video overflow-hidden">
                            <div class="w-full h-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-building text-6xl text-white/50"></i>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex items-center gap-2 text-sm text-slate-500 mb-3">
                                <span class="px-2 py-1 bg-slate-200 dark:bg-slate-700 rounded text-xs">{{ $study['industry'] }}</span>
                                <span>{{ $study['client'] }}</span>
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">{{ $study['title'] }}</h2>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $study['desc'] }}</p>

                            <div class="grid grid-cols-3 gap-4 mb-6">
                                @foreach($study['metrics'] as $metric)
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ $metric['value'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $metric['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <span class="inline-flex items-center gap-2 text-red-600 font-semibold">
                                {{ __('marketing.case_studies.read_more') }}
                                <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                            </span>
                        </div>
                    </article>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('marketing.case_studies.cta_title') }}</h2>
        <p class="text-xl text-white/80 mb-8">{{ __('marketing.case_studies.cta_subtitle') }}</p>
        <a href="{{ route('marketing.demo') }}" class="inline-block px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
            {{ __('marketing.case_studies.cta_button') }}
        </a>
    </div>
</section>
@endsection

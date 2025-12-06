@extends('marketing.layouts.app')

@section('title', $caseStudy->meta_title ?? $caseStudy->title)
@section('meta_description', $caseStudy->meta_description ?? $caseStudy->excerpt)

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Header -->
<header class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="flex items-center justify-center gap-4 text-slate-400 mb-6">
            @if($caseStudy->industry)
                <span class="px-3 py-1 bg-red-600/20 text-red-400 rounded-full text-sm">{{ $caseStudy->industry }}</span>
            @endif
            <span>{{ $caseStudy->client_name }}</span>
        </div>
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-6">{{ $caseStudy->title }}</h1>
        <p class="text-xl text-slate-300">{{ $caseStudy->excerpt }}</p>
    </div>
</header>

<!-- Featured Image -->
@if($caseStudy->image_url)
    <div class="relative -mt-8 mb-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <img src="{{ $caseStudy->image_url }}" alt="{{ $caseStudy->title }}" class="w-full rounded-xl shadow-2xl">
        </div>
    </div>
@endif

<!-- Metrics Banner -->
@if($caseStudy->metrics && count($caseStudy->metrics) > 0)
    <div class="bg-slate-100 dark:bg-slate-800 py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                @foreach($caseStudy->metrics as $metric)
                    <div class="text-center">
                        <div class="text-4xl font-bold text-red-600 mb-2">{{ $metric['value'] }}</div>
                        <div class="text-slate-600 dark:text-slate-400">{{ $metric['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<!-- Content -->
<article class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Challenge -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fas fa-exclamation-triangle"></i>
                </span>
                {{ __('marketing.case_studies.the_challenge') }}
            </h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $caseStudy->challenge !!}
            </div>
        </section>

        <!-- Solution -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fas fa-lightbulb"></i>
                </span>
                {{ __('marketing.case_studies.the_solution') }}
            </h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $caseStudy->solution !!}
            </div>
        </section>

        <!-- Results -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-3">
                <span class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600">
                    <i class="fas fa-chart-line"></i>
                </span>
                {{ __('marketing.case_studies.the_results') }}
            </h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $caseStudy->results !!}
            </div>
        </section>

        <!-- Testimonial -->
        @if($caseStudy->testimonial_quote)
            <section class="mb-12 bg-slate-50 dark:bg-slate-800 rounded-2xl p-8">
                <blockquote class="text-xl text-slate-700 dark:text-slate-300 italic mb-4">
                    "{{ $caseStudy->testimonial_quote }}"
                </blockquote>
                <div class="flex items-center gap-3">
                    @if($caseStudy->testimonial_author_image)
                        <img src="{{ $caseStudy->testimonial_author_image }}" alt="{{ $caseStudy->testimonial_author }}" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center text-white font-bold">
                            {{ mb_substr($caseStudy->testimonial_author, 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">{{ $caseStudy->testimonial_author }}</div>
                        <div class="text-sm text-slate-500">{{ $caseStudy->testimonial_role }}, {{ $caseStudy->client_name }}</div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</article>

<!-- CTA -->
<section class="py-16 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">{{ __('marketing.case_studies.cta_ready') }}</h2>
        <p class="text-white/80 mb-6">{{ __('marketing.case_studies.cta_ready_text') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
                {{ __('marketing.nav.demo') }}
            </a>
            <a href="{{ route('marketing.case-studies.index') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition">
                {{ __('marketing.case_studies.view_all') }}
            </a>
        </div>
    </div>
</section>

<!-- Back Link -->
<div class="py-8 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="{{ route('marketing.case-studies.index') }}" class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-semibold">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
            {{ __('marketing.case_studies.back_to_list') }}
        </a>
    </div>
</div>
@endsection

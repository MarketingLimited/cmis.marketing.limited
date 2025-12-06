@extends('marketing.layouts.app')

@section('title', __('marketing.terms.title'))
@section('meta_description', __('marketing.terms.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ __('marketing.terms.headline') }}</h1>
        <p class="text-slate-400">{{ __('marketing.terms.last_updated') }}: {{ $page->updated_at?->format('F d, Y') ?? now()->format('F d, Y') }}</p>
    </div>
</section>

<!-- Content -->
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(isset($page) && $page->content)
            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white prose-a:text-red-600">
                {!! $page->content !!}
            </div>
        @else
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <h2>{{ __('marketing.terms.section_1_title') }}</h2>
                <p>{{ __('marketing.terms.section_1_content') }}</p>

                <h2>{{ __('marketing.terms.section_2_title') }}</h2>
                <p>{{ __('marketing.terms.section_2_content') }}</p>

                <h2>{{ __('marketing.terms.section_3_title') }}</h2>
                <p>{{ __('marketing.terms.section_3_content') }}</p>

                <h2>{{ __('marketing.terms.section_4_title') }}</h2>
                <p>{{ __('marketing.terms.section_4_content') }}</p>

                <h2>{{ __('marketing.terms.section_5_title') }}</h2>
                <p>{{ __('marketing.terms.section_5_content') }}</p>

                <h2>{{ __('marketing.terms.section_6_title') }}</h2>
                <p>{{ __('marketing.terms.section_6_content') }}</p>
            </div>
        @endif
    </div>
</section>

<!-- Related Links -->
<section class="py-8 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
            <span class="text-slate-500">{{ __('marketing.terms.related') }}:</span>
            <a href="{{ route('marketing.privacy') }}" class="text-red-600 hover:underline">{{ __('marketing.nav.privacy') }}</a>
            <a href="{{ route('marketing.cookies') }}" class="text-red-600 hover:underline">{{ __('marketing.nav.cookies') }}</a>
        </div>
    </div>
</section>
@endsection

@extends('marketing.layouts.app')

@section('title', $page->meta_title ?? $page->title)
@section('meta_description', $page->meta_description ?? Str::limit(strip_tags($page->content), 160))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ $page->title }}</h1>
        @if($page->subtitle)
            <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ $page->subtitle }}</p>
        @endif
    </div>
</section>

<!-- Page Content -->
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-a:text-red-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
            {!! $page->content !!}
        </div>
    </div>
</section>

<!-- Page Sections (if modular) -->
@if($page->sections && $page->sections->isNotEmpty())
    @foreach($page->sections as $section)
        @if($section->type === 'text')
            <section class="py-16 {{ $loop->even ? 'bg-slate-100 dark:bg-slate-800' : 'bg-white dark:bg-slate-900' }}">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    @if($section->title)
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6 text-center">{{ $section->title }}</h2>
                    @endif
                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        {!! $section->content !!}
                    </div>
                </div>
            </section>
        @elseif($section->type === 'cta')
            <section class="py-16 bg-gradient-to-br from-red-600 to-purple-700 text-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    @if($section->title)
                        <h2 class="text-3xl font-bold mb-4">{{ $section->title }}</h2>
                    @endif
                    @if($section->subtitle)
                        <p class="text-xl text-white/80 mb-6">{{ $section->subtitle }}</p>
                    @endif
                    @if($section->cta_url && $section->cta_text)
                        <a href="{{ $section->cta_url }}" class="inline-block px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
                            {{ $section->cta_text }}
                        </a>
                    @endif
                </div>
            </section>
        @elseif($section->type === 'features')
            <section class="py-16 {{ $loop->even ? 'bg-slate-100 dark:bg-slate-800' : 'bg-white dark:bg-slate-900' }}">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    @if($section->title)
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-12 text-center">{{ $section->title }}</h2>
                    @endif
                    @if($section->items)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            @foreach($section->items as $item)
                                <div class="bg-white dark:bg-slate-700 rounded-xl p-6 shadow-sm">
                                    @if($item['icon'] ?? null)
                                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600 mb-4">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </div>
                                    @endif
                                    <h3 class="font-semibold text-slate-900 dark:text-white mb-2">{{ $item['title'] ?? '' }}</h3>
                                    <p class="text-slate-600 dark:text-slate-400 text-sm">{{ $item['description'] ?? '' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        @endif
    @endforeach
@endif

<!-- Last Updated -->
@if($page->updated_at)
    <div class="py-4 bg-slate-100 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-slate-500">
            {{ __('marketing.pages.last_updated') }}: {{ $page->updated_at->format('F d, Y') }}
        </div>
    </div>
@endif
@endsection

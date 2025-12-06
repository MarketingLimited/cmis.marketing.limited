@extends('marketing.layouts.app')

@section('title', $post->meta_title ?? $post->title)
@section('meta_description', $post->meta_description ?? $post->excerpt)

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Article Header -->
<article>
    <header class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            @if($post->category)
                <a href="{{ route('marketing.blog.category', $post->category->slug) }}" class="inline-block px-3 py-1 bg-red-600/20 text-red-400 text-sm font-medium rounded-full mb-4 hover:bg-red-600/30 transition">
                    {{ $post->category->name }}
                </a>
            @endif
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-6">{{ $post->title }}</h1>
            <div class="flex items-center justify-center gap-4 text-slate-400">
                <span>{{ $post->author ?? __('marketing.blog.admin') }}</span>
                <span>•</span>
                <span>{{ $post->published_at?->format('F d, Y') ?? $post->created_at->format('F d, Y') }}</span>
                @if($post->reading_time)
                    <span>•</span>
                    <span>{{ $post->reading_time }} {{ __('marketing.blog.min_read') }}</span>
                @endif
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    @if($post->featured_image_url)
        <div class="relative -mt-8 mb-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full rounded-xl shadow-2xl">
            </div>
        </div>
    @endif

    <!-- Article Content -->
    <div class="py-12 bg-white dark:bg-slate-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-a:text-red-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-xl">
                {!! $post->content !!}
            </div>

            <!-- Tags -->
            @if($post->tags && count($post->tags) > 0)
                <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-slate-600 dark:text-slate-400">{{ __('marketing.blog.tags') }}:</span>
                        @foreach($post->tags as $tag)
                            <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm rounded-full">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Share -->
            <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 dark:text-slate-400">{{ __('marketing.blog.share') }}:</span>
                    <div class="flex items-center gap-4">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-sky-500 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($post->title) }}" target="_blank" rel="noopener" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-blue-700 hover:text-white transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <button onclick="navigator.clipboard.writeText('{{ url()->current() }}')" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-red-600 hover:text-white transition">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<!-- Related Posts -->
@if(isset($relatedPosts) && $relatedPosts->isNotEmpty())
    <section class="py-16 bg-slate-100 dark:bg-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-8 text-center">{{ __('marketing.blog.related_posts') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($relatedPosts as $relatedPost)
                    <article class="bg-white dark:bg-slate-700 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <div class="aspect-video overflow-hidden">
                            @if($relatedPost->featured_image_url)
                                <img src="{{ $relatedPost->featured_image_url }}" alt="{{ $relatedPost->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-newspaper text-4xl text-white/50"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">
                                <a href="{{ route('marketing.blog.show', $relatedPost->slug) }}" class="hover:text-red-600 transition">
                                    {{ $relatedPost->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-slate-500">{{ $relatedPost->published_at?->format('M d, Y') ?? $relatedPost->created_at->format('M d, Y') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- Back to Blog -->
<div class="py-8 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <a href="{{ route('marketing.blog.index') }}" class="inline-flex items-center gap-2 text-red-600 hover:text-red-700 font-semibold">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
            {{ __('marketing.blog.back_to_blog') }}
        </a>
    </div>
</div>
@endsection

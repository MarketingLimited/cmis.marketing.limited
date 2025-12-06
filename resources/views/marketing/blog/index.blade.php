@extends('marketing.layouts.app')

@section('title', __('marketing.blog.title'))
@section('meta_description', __('marketing.blog.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-24 overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-20 {{ $isRtl ? 'right-20' : 'left-20' }} w-72 h-72 bg-orange-600/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-20 {{ $isRtl ? 'left-20' : 'right-20' }} w-96 h-96 bg-pink-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1 bg-orange-600/20 text-orange-400 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-rss me-1"></i> {{ __('marketing.blog.badge') ?? __('marketing.blog.headline') }}
        </span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">{{ __('marketing.blog.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.blog.subheadline') }}</p>
    </div>
</section>

<!-- Blog Content -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Categories -->
        @if(isset($categories) && $categories->isNotEmpty())
            <div class="flex flex-wrap justify-center gap-2 mb-12">
                <a href="{{ route('marketing.blog.index') }}" class="px-4 py-2 rounded-full font-medium transition {{ !request('category') ? 'bg-red-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200' }}">
                    {{ __('marketing.blog.all_posts') }}
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('marketing.blog.category', $category->slug) }}" class="px-4 py-2 rounded-full font-medium transition {{ request()->is('*/' . $category->slug) ? 'bg-red-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-200' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Featured Post -->
        @if(isset($featuredPost) && $featuredPost)
            <div class="mb-16">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="aspect-video lg:aspect-auto">
                            @if($featuredPost->featured_image_url)
                                <img src="{{ $featuredPost->featured_image_url }}" alt="{{ $featuredPost->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center">
                                    <i class="fas fa-newspaper text-6xl text-white/50"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <span class="inline-block px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 text-sm font-medium rounded-full mb-4 w-fit">
                                {{ __('marketing.blog.featured') }}
                            </span>
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white mb-4">
                                <a href="{{ route('marketing.blog.show', $featuredPost->slug) }}" class="hover:text-red-600 transition">
                                    {{ $featuredPost->title }}
                                </a>
                            </h2>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">{{ $featuredPost->excerpt }}</p>
                            <div class="flex items-center gap-4 text-sm text-slate-500">
                                <span>{{ $featuredPost->author ?? __('marketing.blog.admin') }}</span>
                                <span>•</span>
                                <span>{{ $featuredPost->published_at?->format('M d, Y') ?? $featuredPost->created_at->format('M d, Y') }}</span>
                                @if($featuredPost->reading_time)
                                    <span>•</span>
                                    <span>{{ $featuredPost->reading_time }} {{ __('marketing.blog.min_read') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Posts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($posts ?? [] as $post)
                <article class="bg-slate-50 dark:bg-slate-800 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 group">
                    <div class="aspect-video overflow-hidden">
                        @if($post->featured_image_url)
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center">
                                <i class="fas fa-newspaper text-4xl text-white/50"></i>
                            </div>
                        @endif
                    </div>
                    <div class="p-6">
                        @if($post->category)
                            <a href="{{ route('marketing.blog.category', $post->category->slug) }}" class="text-xs text-red-600 font-medium uppercase tracking-wide mb-2 inline-block hover:text-red-700">
                                {{ $post->category->name }}
                            </a>
                        @endif
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">
                            <a href="{{ route('marketing.blog.show', $post->slug) }}" class="hover:text-red-600 transition">
                                {{ $post->title }}
                            </a>
                        </h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mb-4 line-clamp-3">{{ $post->excerpt }}</p>
                        <div class="flex items-center justify-between text-sm text-slate-500">
                            <span>{{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}</span>
                            @if($post->reading_time)
                                <span>{{ $post->reading_time }} {{ __('marketing.blog.min_read') }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-newspaper text-6xl text-slate-300 dark:text-slate-600 mb-4"></i>
                    <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.blog.no_posts') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if(isset($posts) && $posts->hasPages())
            <div class="mt-12 flex justify-center">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</section>

<!-- Newsletter CTA -->
<section class="py-16 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.blog.newsletter_title') }}</h2>
        <p class="text-slate-600 dark:text-slate-400 mb-6">{{ __('marketing.blog.newsletter_subtitle') }}</p>
        <form action="{{ route('marketing.newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
            @csrf
            <input type="email" name="email" required placeholder="{{ __('marketing.blog.email_placeholder') }}"
                   class="flex-1 px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
            <button type="submit" class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                {{ __('marketing.blog.subscribe') }}
            </button>
        </form>
    </div>
</section>
@endsection

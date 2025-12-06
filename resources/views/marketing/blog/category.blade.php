@extends('marketing.layouts.app')

@section('title', $category->name . ' - ' . __('marketing.blog.title'))
@section('meta_description', $category->description ?? __('marketing.blog.category_meta', ['name' => $category->name]))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-red-400 mb-2">{{ __('marketing.blog.category') }}</p>
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ $category->name }}</h1>
        @if($category->description)
            <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ $category->description }}</p>
        @endif
    </div>
</section>

<!-- Blog Posts -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back to All -->
        <div class="mb-8">
            <a href="{{ route('marketing.blog.index') }}" class="inline-flex items-center gap-2 text-red-600 hover:text-red-700">
                <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
                {{ __('marketing.blog.all_posts') }}
            </a>
        </div>

        <!-- Posts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($posts as $post)
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
                    <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.blog.no_posts_in_category') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($posts->hasPages())
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

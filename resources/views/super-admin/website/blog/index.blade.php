@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.website.blog_title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.blog_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.blog_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.website.blog-categories.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                <i class="fas fa-folder"></i>
                {{ __('super_admin.website.categories') }}
            </a>
            <a href="{{ route('super-admin.website.blog.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-plus"></i>
                {{ __('super_admin.website.create_post') }}
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-newspaper text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.total_posts') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['published'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.published') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <i class="fas fa-edit text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['draft'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.draft') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <i class="fas fa-star text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['featured'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.featured') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <form action="{{ route('super-admin.website.blog.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.website.search_posts') }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <select name="category" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.website.all_categories') }}</option>
                @foreach($categories ?? [] as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.website.all_statuses') }}</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>{{ __('super_admin.website.published') }}</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('super_admin.website.draft') }}</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                <i class="fas fa-search me-1"></i>
                {{ __('super_admin.common.filter') }}
            </button>
            @if(request()->hasAny(['search', 'category', 'status']))
                <a href="{{ route('super-admin.website.blog.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                    {{ __('super_admin.common.clear') }}
                </a>
            @endif
        </form>
    </div>

    <!-- Blog Posts List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($posts->count() > 0)
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($posts as $post)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            <!-- Featured Image -->
                            @if($post->featured_image_url)
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-24 h-16 rounded-lg object-cover flex-shrink-0">
                            @else
                                <div class="w-24 h-16 bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-image text-slate-400 text-xl"></i>
                                </div>
                            @endif

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('super-admin.website.blog.edit', $post->id) }}"
                                       class="text-lg font-semibold text-slate-900 dark:text-white hover:text-red-600 dark:hover:text-red-400">
                                        {{ $post->title }}
                                    </a>
                                    @if($post->is_featured)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 rounded-full">
                                            <i class="fas fa-star me-1"></i>{{ __('super_admin.website.featured') }}
                                        </span>
                                    @endif
                                    @if($post->is_published)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            {{ __('super_admin.website.published') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            {{ __('super_admin.website.draft') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 mt-1 line-clamp-2 text-sm">{{ $post->excerpt ?? Str::limit(strip_tags($post->content), 150) }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                                    @if($post->category)
                                        <span><i class="fas fa-folder me-1"></i>{{ $post->category->name }}</span>
                                    @endif
                                    <span><i class="fas fa-clock me-1"></i>{{ $post->reading_time ?? 5 }} {{ __('super_admin.website.min_read') }}</span>
                                    <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at ? $post->published_at->format('M j, Y') : $post->created_at->format('M j, Y') }}</span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="/blog/{{ $post->slug }}" target="_blank"
                                   class="p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                   title="{{ __('super_admin.website.view_post') }}">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="{{ route('super-admin.website.blog.edit', $post->id) }}"
                                   class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                   title="{{ __('super_admin.common.edit') }}">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('super-admin.website.blog.destroy', $post->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('{{ __('super_admin.website.confirm_delete_post') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                            title="{{ __('super_admin.common.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($posts->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $posts->links() }}
                </div>
            @endif
        @else
            <div class="p-8 text-center">
                <i class="fas fa-newspaper text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_posts') }}</p>
                <a href="{{ route('super-admin.website.blog.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus"></i>
                    {{ __('super_admin.website.create_first_post') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

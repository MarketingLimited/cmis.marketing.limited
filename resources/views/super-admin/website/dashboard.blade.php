@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.website.dashboard_title'))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.dashboard_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.dashboard_subtitle') }}</p>
        </div>
        <a href="{{ route('marketing.home') }}" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-external-link-alt"></i>
            {{ __('super_admin.website.view_live_site') }}
        </a>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Published Pages -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-file-alt text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['pages']['published'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.published_pages') }}</p>
                </div>
            </div>
        </div>

        <!-- Blog Posts -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-newspaper text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['blog']['published'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.blog_posts') }}</p>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <i class="fas fa-star text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['features']['active'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.features') }}</p>
                </div>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <i class="fas fa-quote-right text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['testimonials']['active'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.testimonials') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Hero Slides -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <i class="fas fa-images text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['hero_slides']['active'] ?? 0 }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('super_admin.website.hero_slides') }}</p>
                </div>
            </div>
        </div>

        <!-- Case Studies -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                    <i class="fas fa-briefcase text-cyan-600 dark:text-cyan-400"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['case_studies']['published'] ?? 0 }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('super_admin.website.case_studies') }}</p>
                </div>
            </div>
        </div>

        <!-- FAQs -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <i class="fas fa-question-circle text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['faqs']['active'] ?? 0 }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('super_admin.website.faqs') }}</p>
                </div>
            </div>
        </div>

        <!-- Team Members -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                    <i class="fas fa-users text-pink-600 dark:text-pink-400"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['team']['active'] ?? 0 }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('super_admin.website.team_members') }}</p>
                </div>
            </div>
        </div>

        <!-- Partners -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-teal-100 dark:bg-teal-900/30 rounded-lg">
                    <i class="fas fa-handshake text-teal-600 dark:text-teal-400"></i>
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900 dark:text-white">{{ $stats['partners']['active'] ?? 0 }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">{{ __('super_admin.website.partners') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Blog Posts -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('super_admin.website.recent_blog_posts') }}</h2>
                <a href="{{ route('super-admin.website.blog.index') }}" class="text-sm text-red-600 hover:text-red-700">
                    {{ __('super_admin.common.view_all') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} ms-1"></i>
                </a>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @forelse($recentBlogPosts ?? [] as $post)
                    <div class="px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                        <div class="flex items-start gap-3">
                            @if($post->featured_image_url)
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-slate-400"></i>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('super-admin.website.blog.edit', $post->id) }}" class="font-medium text-slate-900 dark:text-white hover:text-red-600 line-clamp-1">
                                    {{ $post->title }}
                                </a>
                                <p class="text-sm text-slate-500 mt-1">{{ $post->created_at->format('M j, Y') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $post->is_published ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ $post->is_published ? __('super_admin.website.published') : __('super_admin.website.draft') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-slate-500">
                        <i class="fas fa-newspaper text-3xl mb-2"></i>
                        <p>{{ __('super_admin.website.no_blog_posts') }}</p>
                        <a href="{{ route('super-admin.website.blog.create') }}" class="inline-flex items-center gap-2 mt-3 text-red-600 hover:text-red-700">
                            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_post') }}
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('super_admin.website.quick_actions') }}</h2>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <a href="{{ route('super-admin.website.pages.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-plus text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_page') }}</span>
                </a>
                <a href="{{ route('super-admin.website.blog.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-pen-fancy text-green-600 dark:text-green-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_blog_post') }}</span>
                </a>
                <a href="{{ route('super-admin.website.hero.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-indigo-600 dark:text-indigo-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_hero_slide') }}</span>
                </a>
                <a href="{{ route('super-admin.website.testimonials.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-comment-dots text-yellow-600 dark:text-yellow-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_testimonial') }}</span>
                </a>
                <a href="{{ route('super-admin.website.features.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-star text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_feature') }}</span>
                </a>
                <a href="{{ route('super-admin.website.faqs.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition text-center">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <i class="fas fa-question text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.new_faq') }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Content Management Links -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ __('super_admin.website.content_management') }}</h2>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="{{ route('super-admin.website.pages.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-file-alt text-2xl text-blue-600 dark:text-blue-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.pages') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['pages'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.hero.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-images text-2xl text-indigo-600 dark:text-indigo-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.hero_slides') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['hero_slides'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.features.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-star text-2xl text-purple-600 dark:text-purple-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.features') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['features'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.testimonials.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-quote-right text-2xl text-yellow-600 dark:text-yellow-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.testimonials') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['testimonials'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.case-studies.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-briefcase text-2xl text-cyan-600 dark:text-cyan-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.case_studies') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['case_studies'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.faqs.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-question-circle text-2xl text-orange-600 dark:text-orange-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.faqs') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['faqs'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.team.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-users text-2xl text-pink-600 dark:text-pink-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.team_members') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['team_members'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.partners.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-handshake text-2xl text-teal-600 dark:text-teal-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.partners') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['partners'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.blog.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-newspaper text-2xl text-green-600 dark:text-green-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.blog') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['blog_posts'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.navigation.index') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-bars text-2xl text-slate-600 dark:text-slate-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.navigation') }}</span>
                <span class="text-xs text-slate-500">{{ $stats['navigation_menus'] ?? 0 }}</span>
            </a>
            <a href="{{ route('super-admin.website.settings') }}" class="flex flex-col items-center gap-2 p-4 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 transition text-center">
                <i class="fas fa-cog text-2xl text-slate-600 dark:text-slate-400"></i>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('super_admin.website.settings') }}</span>
            </a>
        </div>
    </div>
</div>
@endsection

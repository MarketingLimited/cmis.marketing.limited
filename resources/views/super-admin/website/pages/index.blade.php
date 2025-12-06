@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.website.pages_title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.pages_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.pages_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.pages.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-plus"></i>
            {{ __('super_admin.website.create_page') }}
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-file-alt text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.total_pages') }}</p>
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
                    <i class="fas fa-layer-group text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['templates'] ?? 0 }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('super_admin.website.templates') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
        <form action="{{ route('super-admin.website.pages.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.website.search_pages') }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-red-500 focus:border-red-500">
            </div>
            <select name="status" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.website.all_statuses') }}</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>{{ __('super_admin.website.published') }}</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('super_admin.website.draft') }}</option>
            </select>
            <select name="template" class="px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white">
                <option value="">{{ __('super_admin.website.all_templates') }}</option>
                <option value="default" {{ request('template') == 'default' ? 'selected' : '' }}>Default</option>
                <option value="legal" {{ request('template') == 'legal' ? 'selected' : '' }}>Legal</option>
                <option value="landing" {{ request('template') == 'landing' ? 'selected' : '' }}>Landing</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                <i class="fas fa-search me-1"></i>
                {{ __('super_admin.common.filter') }}
            </button>
            @if(request()->hasAny(['search', 'status', 'template']))
                <a href="{{ route('super-admin.website.pages.index') }}" class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                    {{ __('super_admin.common.clear') }}
                </a>
            @endif
        </form>
    </div>

    <!-- Pages List -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($pages->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.website.page') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.website.slug') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.website.template') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.website.status') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.website.updated') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('super_admin.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($pages as $page)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-file-alt text-blue-600 dark:text-blue-400"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('super-admin.website.pages.edit', $page->id) }}" class="font-medium text-slate-900 dark:text-white hover:text-red-600">
                                                {{ $page->title }}
                                            </a>
                                            @if($page->title_ar && app()->getLocale() === 'en')
                                                <p class="text-xs text-slate-500">{{ Str::limit($page->title_ar, 40) }}</p>
                                            @elseif($page->title_en && app()->getLocale() === 'ar')
                                                <p class="text-xs text-slate-500">{{ Str::limit($page->title_en, 40) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <code class="px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded text-sm text-slate-700 dark:text-slate-300">/{{ $page->slug }}</code>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 capitalize">
                                    {{ $page->template ?? 'default' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($page->is_published)
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                            {{ __('super_admin.website.published') }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded-full">
                                            {{ __('super_admin.website.draft') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    {{ $page->updated_at->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/{{ $page->slug }}" target="_blank"
                                           class="p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700 rounded-lg transition-colors"
                                           title="{{ __('super_admin.website.view_page') }}">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <a href="{{ route('super-admin.website.pages.edit', $page->id) }}"
                                           class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                                           title="{{ __('super_admin.common.edit') }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('super-admin.website.pages.destroy', $page->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('{{ __('super_admin.website.confirm_delete_page') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                                    title="{{ __('super_admin.common.delete') }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($pages->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    {{ $pages->links() }}
                </div>
            @endif
        @else
            <div class="p-8 text-center">
                <i class="fas fa-file-alt text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_pages') }}</p>
                <a href="{{ route('super-admin.website.pages.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus"></i>
                    {{ __('super_admin.website.create_first_page') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

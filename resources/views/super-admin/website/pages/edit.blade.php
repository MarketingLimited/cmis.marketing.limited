@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.website.edit_page'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.website.pages.index') }}"
           class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.edit_page') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ $page->title }}</p>
        </div>
        <a href="/{{ $page->slug }}" target="_blank"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-external-link-alt"></i>
            {{ __('super_admin.website.view_page') }}
        </a>
    </div>

    <!-- Form -->
    <form action="{{ route('super-admin.website.pages.update', $page->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- English Content -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded flex items-center justify-center text-xs font-bold text-blue-600">EN</span>
                        {{ __('super_admin.website.english_content') }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.title') }} (English) <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title_en" value="{{ old('title_en', $page->title_en) }}" required
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="Page Title">
                            @error('title_en')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.content') }} (English)
                            </label>
                            <textarea name="content_en" rows="10"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Page content...">{{ old('content_en', $page->content_en) }}</textarea>
                            @error('content_en')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Arabic Content -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded flex items-center justify-center text-xs font-bold text-green-600">AR</span>
                        {{ __('super_admin.website.arabic_content') }}
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.title') }} (Arabic)
                            </label>
                            <input type="text" name="title_ar" value="{{ old('title_ar', $page->title_ar) }}" dir="rtl"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="عنوان الصفحة">
                            @error('title_ar')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.content') }} (Arabic)
                            </label>
                            <textarea name="content_ar" rows="10" dir="rtl"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="محتوى الصفحة...">{{ old('content_ar', $page->content_ar) }}</textarea>
                            @error('content_ar')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Page Settings -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.page_settings') }}</h3>

                    <div class="space-y-4">
                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.slug') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-500">/</span>
                                <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" required
                                       class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       placeholder="page-url-slug">
                            </div>
                            @error('slug')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Template -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.template') }}
                            </label>
                            <select name="template"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="default" {{ old('template', $page->template) == 'default' ? 'selected' : '' }}>Default</option>
                                <option value="legal" {{ old('template', $page->template) == 'legal' ? 'selected' : '' }}>Legal</option>
                                <option value="landing" {{ old('template', $page->template) == 'landing' ? 'selected' : '' }}>Landing</option>
                                <option value="full-width" {{ old('template', $page->template) == 'full-width' ? 'selected' : '' }}>Full Width</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('super_admin.website.publish_page') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.seo_settings') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_title') }} (EN)
                            </label>
                            <input type="text" name="meta_title_en" value="{{ old('meta_title_en', $page->seoMetadata->meta_title_en ?? '') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="SEO Title">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_title') }} (AR)
                            </label>
                            <input type="text" name="meta_title_ar" value="{{ old('meta_title_ar', $page->seoMetadata->meta_title_ar ?? '') }}" dir="rtl"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="عنوان SEO">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_description') }} (EN)
                            </label>
                            <textarea name="meta_description_en" rows="2"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="SEO Description">{{ old('meta_description_en', $page->seoMetadata->meta_description_en ?? '') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_description') }} (AR)
                            </label>
                            <textarea name="meta_description_ar" rows="2" dir="rtl"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="وصف SEO">{{ old('meta_description_ar', $page->seoMetadata->meta_description_ar ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Page Info -->
                <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-200 dark:border-slate-600">
                    <h4 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">{{ __('super_admin.website.page_info') }}</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('super_admin.website.created') }}:</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $page->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">{{ __('super_admin.website.updated') }}:</span>
                            <span class="text-slate-700 dark:text-slate-300">{{ $page->updated_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-save me-2"></i>
                        {{ __('super_admin.website.update_page') }}
                    </button>
                    <a href="{{ route('super-admin.website.pages.index') }}"
                       class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        {{ __('super_admin.common.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

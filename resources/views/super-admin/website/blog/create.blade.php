@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.website.create_post'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.website.blog.index') }}"
           class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.create_post') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.create_post_subtitle') }}</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('super-admin.website.blog.store') }}" method="POST" class="space-y-6">
        @csrf

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
                            <input type="text" name="title_en" value="{{ old('title_en') }}" required
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="Post Title">
                            @error('title_en')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.excerpt') }} (English)
                            </label>
                            <textarea name="excerpt_en" rows="3"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Brief summary for previews...">{{ old('excerpt_en') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.content') }} (English) <span class="text-red-500">*</span>
                            </label>
                            <textarea name="content_en" rows="15" required
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="Write your blog post content here...">{{ old('content_en') }}</textarea>
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
                            <input type="text" name="title_ar" value="{{ old('title_ar') }}" dir="rtl"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="عنوان المقال">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.excerpt') }} (Arabic)
                            </label>
                            <textarea name="excerpt_ar" rows="3" dir="rtl"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="ملخص موجز للمعاينات...">{{ old('excerpt_ar') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.content') }} (Arabic)
                            </label>
                            <textarea name="content_ar" rows="15" dir="rtl"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="اكتب محتوى المقال هنا...">{{ old('content_ar') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Post Settings -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.post_settings') }}</h3>

                    <div class="space-y-4">
                        <!-- Slug -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.slug') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-slate-500">/blog/</span>
                                <input type="text" name="slug" value="{{ old('slug') }}" required
                                       class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       placeholder="post-url-slug">
                            </div>
                            @error('slug')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.category') }}
                            </label>
                            <select name="category_id"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">{{ __('super_admin.website.select_category') }}</option>
                                @foreach($categories ?? [] as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Author -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.author_name') }}
                            </label>
                            <input type="text" name="author_name" value="{{ old('author_name') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="Author Name">
                        </div>

                        <!-- Featured Image URL -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.featured_image') }}
                            </label>
                            <input type="url" name="featured_image_url" value="{{ old('featured_image_url') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="https://example.com/image.jpg">
                        </div>

                        <!-- Status -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('super_admin.website.publish_post') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('super_admin.website.feature_post') }}</span>
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
                            <input type="text" name="meta_title_en" value="{{ old('meta_title_en') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_title') }} (AR)
                            </label>
                            <input type="text" name="meta_title_ar" value="{{ old('meta_title_ar') }}" dir="rtl"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_description') }} (EN)
                            </label>
                            <textarea name="meta_description_en" rows="2"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('meta_description_en') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.website.meta_description') }} (AR)
                            </label>
                            <textarea name="meta_description_ar" rows="2" dir="rtl"
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('meta_description_ar') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-save me-2"></i>
                        {{ __('super_admin.website.save_post') }}
                    </button>
                    <a href="{{ route('super-admin.website.blog.index') }}"
                       class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        {{ __('super_admin.common.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

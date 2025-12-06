@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.settings_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.settings_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.settings_subtitle') }}</p>
        </div>
    </div>

    <form action="{{ route('super-admin.website.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.general_settings') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.site_name') }} (EN)</label>
                        <input type="text" name="site_name_en" value="{{ $settings['site_name_en'] ?? config('app.name') }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.site_name') }} (AR)</label>
                        <input type="text" name="site_name_ar" value="{{ $settings['site_name_ar'] ?? '' }}" dir="rtl"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.tagline') }} (EN)</label>
                        <input type="text" name="tagline_en" value="{{ $settings['tagline_en'] ?? '' }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.tagline') }} (AR)</label>
                        <input type="text" name="tagline_ar" value="{{ $settings['tagline_ar'] ?? '' }}" dir="rtl"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
            </div>

            <!-- Contact Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.contact_settings') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.contact_email') }}</label>
                        <input type="email" name="contact_email" value="{{ $settings['contact_email'] ?? '' }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.contact_phone') }}</label>
                        <input type="text" name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.address') }} (EN)</label>
                        <textarea name="address_en" rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ $settings['address_en'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.address') }} (AR)</label>
                        <textarea name="address_ar" rows="2" dir="rtl" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ $settings['address_ar'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Social Media -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.social_media') }}</h3>
                <div class="space-y-4">
                    @foreach(['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'] as $platform)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1 capitalize">
                                <i class="fab fa-{{ $platform }} me-1"></i> {{ ucfirst($platform) }}
                            </label>
                            <input type="url" name="social_{{ $platform }}" value="{{ $settings['social_' . $platform] ?? '' }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500"
                                   placeholder="https://{{ $platform }}.com/...">
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- SEO Defaults -->
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.website.seo_defaults') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.default_meta_title') }} (EN)</label>
                        <input type="text" name="default_meta_title_en" value="{{ $settings['default_meta_title_en'] ?? '' }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.default_meta_title') }} (AR)</label>
                        <input type="text" name="default_meta_title_ar" value="{{ $settings['default_meta_title_ar'] ?? '' }}" dir="rtl"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.default_meta_description') }} (EN)</label>
                        <textarea name="default_meta_description_en" rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ $settings['default_meta_description_en'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">{{ __('super_admin.website.default_meta_description') }} (AR)</label>
                        <textarea name="default_meta_description_ar" rows="2" dir="rtl" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">{{ $settings['default_meta_description_ar'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-save me-2"></i>
                {{ __('super_admin.website.save_settings') }}
            </button>
        </div>
    </form>
</div>
@endsection

@extends('settings.platforms._platform-layout')

@section('platform-icon')
<i class="fab fa-google text-xl text-red-500"></i>
@endsection

@section('platform-icon-small')
<i class="fab fa-google text-red-500"></i>
@endsection

@section('platform-title', __('settings.platform_google'))
@section('platform-subtitle', __('settings.platform_google_subtitle'))

@section('platform-specific-settings')
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.google_specific_settings') }}</h2>

    <div class="space-y-6">
        {{-- Conversion ID --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('settings.google_conversion_id') }}
            </label>
            <input type="text" name="conversion_id"
                   value="{{ $settings['platform_google_conversion_id'] ?? '' }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="AW-123456789">
            <p class="text-xs text-gray-500 mt-1">{{ __('settings.google_conversion_id_help') }}</p>
        </div>

        {{-- Auto Tagging --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="auto_tagging" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_google_auto_tagging'] ?? true)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_auto_tagging') }}</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ms-6">{{ __('settings.auto_tagging_help') }}</p>
        </div>

        {{-- Enhanced Conversions --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="enhanced_conversions" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_google_enhanced_conversions'] ?? false)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_enhanced_conversions') }}</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ms-6">{{ __('settings.enhanced_conversions_help') }}</p>
        </div>

        {{-- Conversion Tracking --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="conversion_tracking" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_google_conversion_tracking'] ?? true)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_conversion_tracking') }}</span>
            </label>
        </div>
    </div>
</div>
@endsection

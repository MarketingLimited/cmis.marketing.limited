@extends('settings.platforms._platform-layout')

@section('platform-icon')
<i class="fab fa-tiktok text-xl text-gray-900"></i>
@endsection

@section('platform-icon-small')
<i class="fab fa-tiktok text-gray-900"></i>
@endsection

@section('platform-title', __('settings.platform_tiktok'))
@section('platform-subtitle', __('settings.platform_tiktok_subtitle'))

@section('platform-specific-settings')
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.tiktok_specific_settings') }}</h2>

    <div class="space-y-6">
        {{-- Pixel Code --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('settings.tiktok_pixel_code') }}
            </label>
            <input type="text" name="pixel_code"
                   value="{{ $settings['platform_tiktok_pixel_code'] ?? '' }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="CXXXXXXXXXXXXXXXXX">
            <p class="text-xs text-gray-500 mt-1">{{ __('settings.tiktok_pixel_help') }}</p>
        </div>

        {{-- Events API --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="events_api" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_tiktok_events_api'] ?? false)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_events_api') }}</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ms-6">{{ __('settings.tiktok_events_api_help') }}</p>
        </div>

        {{-- Conversion Tracking --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="conversion_tracking" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_tiktok_conversion_tracking'] ?? true)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_conversion_tracking') }}</span>
            </label>
        </div>
    </div>
</div>
@endsection

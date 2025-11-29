@extends('settings.platforms._platform-layout')

@section('platform-icon')
<i class="fab fa-linkedin text-xl text-blue-700"></i>
@endsection

@section('platform-icon-small')
<i class="fab fa-linkedin text-blue-700"></i>
@endsection

@section('platform-title', __('settings.platform_linkedin'))
@section('platform-subtitle', __('settings.platform_linkedin_subtitle'))

@section('platform-specific-settings')
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.linkedin_specific_settings') }}</h2>

    <div class="space-y-6">
        {{-- Insight Tag ID --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ __('settings.linkedin_insight_tag') }}
            </label>
            <input type="text" name="insight_tag_id"
                   value="{{ $settings['platform_linkedin_insight_tag_id'] ?? '' }}"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="123456">
            <p class="text-xs text-gray-500 mt-1">{{ __('settings.linkedin_insight_tag_help') }}</p>
        </div>

        {{-- Conversion API --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="conversion_api" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_linkedin_conversion_api'] ?? false)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_conversion_api') }}</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ms-6">{{ __('settings.linkedin_capi_help') }}</p>
        </div>

        {{-- Conversion Tracking --}}
        <div>
            <label class="flex items-center gap-3">
                <input type="checkbox" name="conversion_tracking" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       @checked($settings['platform_linkedin_conversion_tracking'] ?? true)>
                <span class="text-sm font-medium text-gray-700">{{ __('settings.enable_conversion_tracking') }}</span>
            </label>
        </div>
    </div>
</div>
@endsection

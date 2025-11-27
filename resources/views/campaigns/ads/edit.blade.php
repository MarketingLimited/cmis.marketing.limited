@extends('layouts.admin')

@section('title', __('campaigns.edit_ad') . ' - ' . $ad->name)

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" x-data="adForm()" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('campaigns.campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 15) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('campaigns.edit') }}: {{ $ad->name }}</span>
        </nav>
    </div>

    <div class="mb-6 {{ $isRtl ? 'text-right' : '' }}">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('campaigns.edit_ad') }}</h1>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc {{ $isRtl ? 'list-inside text-right' : 'list-inside' }}">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('org.campaigns.ad-sets.ads.update', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.basic_information') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_name') }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $ad->name) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                               dir="{{ $dir }}">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.status_label') }}</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                @foreach(['draft', 'active', 'paused', 'completed', 'archived'] as $status)
                                    <option value="{{ $status }}" {{ old('status', $ad->status) === $status ? 'selected' : '' }}>{{ __('campaigns.status.' . $status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="ad_format" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_format_label') }}</label>
                            <select name="ad_format" id="ad_format" x-model="adFormat" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                @foreach($adFormats as $value => $label)
                                    <option value="{{ $value }}" {{ old('ad_format', $ad->ad_format) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Creative Content --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.creative_content') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="primary_text" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.primary_text_label') }}</label>
                        <textarea name="primary_text" id="primary_text" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                  dir="{{ $dir }}">{{ old('primary_text', $ad->primary_text) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="headline" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.headline_label') }}</label>
                            <input type="text" name="headline" id="headline" value="{{ old('headline', $ad->headline) }}" maxlength="255"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                   dir="{{ $dir }}">
                        </div>
                        <div>
                            <label for="call_to_action" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.call_to_action_label') }}</label>
                            <select name="call_to_action" id="call_to_action" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                <option value="">{{ __('campaigns.select_cta') }}</option>
                                @foreach($callToActions as $value => $label)
                                    <option value="{{ $value }}" {{ old('call_to_action', $ad->call_to_action) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="description_text" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.description_label') }}</label>
                        <textarea name="description_text" id="description_text" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                  dir="{{ $dir }}">{{ old('description_text', $ad->description_text) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.media') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.image_url_label') }}</label>
                        <input type="url" name="image_url" id="image_url" value="{{ old('image_url', $ad->image_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               dir="ltr">
                    </div>
                    <div>
                        <label for="video_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.video_url_label') }}</label>
                        <input type="url" name="video_url" id="video_url" value="{{ old('video_url', $ad->video_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               dir="ltr">
                    </div>
                </div>
                <div class="mt-4">
                    <label for="thumbnail_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.thumbnail_url_label') }}</label>
                    <input type="url" name="thumbnail_url" id="thumbnail_url" value="{{ old('thumbnail_url', $ad->thumbnail_url) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           dir="ltr">
                </div>
            </div>
        </div>

        {{-- Destination & Tracking --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.destination_tracking') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="destination_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.destination_url_label') }}</label>
                        <input type="url" name="destination_url" id="destination_url" value="{{ old('destination_url', $ad->destination_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               dir="ltr">
                    </div>
                    <div>
                        <label for="display_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.display_url_label') }}</label>
                        <input type="text" name="display_url" id="display_url" value="{{ old('display_url', $ad->display_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               dir="ltr">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @php $urlParams = $ad->url_parameters ?? []; @endphp
                        <input type="text" name="url_parameters[utm_source]" value="{{ old('url_parameters.utm_source', $urlParams['utm_source'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __('campaigns.utm_source_placeholder') }}"
                               dir="ltr">
                        <input type="text" name="url_parameters[utm_medium]" value="{{ old('url_parameters.utm_medium', $urlParams['utm_medium'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __('campaigns.utm_medium_placeholder') }}"
                               dir="ltr">
                        <input type="text" name="url_parameters[utm_campaign]" value="{{ old('url_parameters.utm_campaign', $urlParams['utm_campaign'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __('campaigns.utm_campaign_placeholder') }}"
                               dir="ltr">
                    </div>
                    <div>
                        <label for="tracking_pixel_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.tracking_pixel_id_label') }}</label>
                        <input type="text" name="tracking_pixel_id" id="tracking_pixel_id" value="{{ old('tracking_pixel_id', $ad->tracking_pixel_id) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               dir="ltr">
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }}">
            <form action="{{ route('org.campaigns.ad-sets.ads.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                  method="POST" onsubmit="return confirm('{{ __('campaigns.delete_ad_confirm') }}');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm text-red-600 hover:text-red-700 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-trash {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i> {{ __('campaigns.delete') }}
                </button>
            </form>
            <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
                <a href="{{ route('org.campaigns.ad-sets.ads.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">{{ __('campaigns.cancel') }}</a>
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">{{ __('campaigns.save_changes') }}</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adForm() {
    return { adFormat: '{{ old('ad_format', $ad->ad_format ?? 'image') }}' }
}
</script>
@endpush
@endsection

@extends('layouts.admin')

@section('title', __('campaigns.create_ad') . ' - ' . $adSet->name)

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
            <a href="{{ route('orgs.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 20) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('campaigns.create_ad') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6 {{ $isRtl ? 'text-right' : '' }}">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('campaigns.create_ad') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('campaigns.ad_set') }}: <span class="font-medium">{{ $adSet->name }}</span>
            @if($campaign->platform)
                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ ucfirst($campaign->platform) }}
                </span>
            @endif
        </p>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc {{ $isRtl ? 'list-inside text-right' : 'list-inside' }}">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('org.campaigns.ad-sets.ads.store', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.basic_information') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_name') }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                               dir="{{ $dir }}">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.status_label') }}</label>
                            <select name="status" id="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                <option value="draft">{{ __('campaigns.status.draft') }}</option>
                                <option value="active">{{ __('campaigns.status.active') }}</option>
                                <option value="paused">{{ __('campaigns.status.paused') }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="ad_format" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_format_label') }} *</label>
                            <select name="ad_format" id="ad_format" x-model="adFormat" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                @foreach($adFormats as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
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
                                  placeholder="{{ __('campaigns.primary_text_placeholder') }}"
                                  dir="{{ $dir }}">{{ old('primary_text') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.primary_text_hint') }}</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="headline" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.headline_label') }}</label>
                            <input type="text" name="headline" id="headline" value="{{ old('headline') }}" maxlength="255"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                   placeholder="{{ __('campaigns.headline_placeholder') }}"
                                   dir="{{ $dir }}">
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.headline_hint') }}</p>
                        </div>
                        <div>
                            <label for="call_to_action" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.call_to_action_label') }}</label>
                            <select name="call_to_action" id="call_to_action"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                    dir="{{ $dir }}">
                                <option value="">{{ __('campaigns.select_cta') }}</option>
                                @foreach($callToActions as $value => $label)
                                    <option value="{{ $value }}" {{ old('call_to_action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="description_text" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.description_label') }}</label>
                        <textarea name="description_text" id="description_text" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                  placeholder="{{ __('campaigns.description_placeholder') }}"
                                  dir="{{ $dir }}">{{ old('description_text') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.media') }}</h3>

                {{-- Single Image/Video --}}
                <div x-show="adFormat === 'image' || adFormat === 'video' || adFormat === 'stories' || adFormat === 'reels'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.image_url_label') }}</label>
                            <input type="url" name="image_url" id="image_url" value="{{ old('image_url') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="{{ __('campaigns.image_url_placeholder') }}"
                                   dir="ltr">
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.image_url_hint') }}</p>
                        </div>
                        <div>
                            <label for="video_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.video_url_label') }}</label>
                            <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="{{ __('campaigns.video_url_placeholder') }}"
                                   dir="ltr">
                            <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.video_url_hint') }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="thumbnail_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.thumbnail_url_label') }}</label>
                        <input type="url" name="thumbnail_url" id="thumbnail_url" value="{{ old('thumbnail_url') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="{{ __('campaigns.thumbnail_url_placeholder') }}"
                               dir="ltr">
                    </div>
                </div>

                {{-- Carousel Cards --}}
                <div x-show="adFormat === 'carousel'" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.carousel_cards_label') }}</label>
                    <div class="space-y-4" x-data="{ cards: [{}] }">
                        <template x-for="(card, index) in cards" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} items-center mb-3">
                                    <span class="text-sm font-medium text-gray-700">{{ __('campaigns.card') }} <span x-text="index + 1"></span></span>
                                    <button type="button" @click="cards.splice(index, 1)" x-show="cards.length > 1"
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        <i class="fas fa-times"></i> {{ __('campaigns.remove') }}
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input type="url" :name="'carousel_cards[' + index + '][image_url]'" placeholder="{{ __('campaigns.image_url_label') }}"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           dir="ltr">
                                    <input type="text" :name="'carousel_cards[' + index + '][headline]'" placeholder="{{ __('campaigns.headline_label') }}" maxlength="40"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                           dir="{{ $dir }}">
                                    <input type="text" :name="'carousel_cards[' + index + '][description]'" placeholder="{{ __('campaigns.description_label') }}" maxlength="125"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                           dir="{{ $dir }}">
                                    <input type="url" :name="'carousel_cards[' + index + '][link]'" placeholder="{{ __('campaigns.destination_url_placeholder') }}"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                           dir="ltr">
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="if(cards.length < 10) cards.push({})"
                                class="text-blue-600 hover:text-blue-800 text-sm {{ $isRtl ? 'flex flex-row-reverse items-center' : '' }}" x-show="cards.length < 10">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i> {{ __('campaigns.add_card') }}
                        </button>
                    </div>
                </div>

                {{-- Dynamic Creative Toggle --}}
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <label class="flex items-center {{ $isRtl ? 'flex-row-reverse justify-end' : '' }}">
                        <input type="checkbox" name="is_dynamic_creative" value="1" x-model="isDynamicCreative"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm font-medium text-gray-700">{{ __('campaigns.enable_dynamic_creative') }}</span>
                    </label>
                    <p class="mt-1 {{ $isRtl ? 'mr-6 text-right' : 'ml-6' }} text-xs text-gray-500">{{ __('campaigns.dynamic_creative_description') }}</p>
                </div>
            </div>
        </div>

        {{-- Destination & Tracking --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.destination_tracking') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="destination_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.destination_url_label') }} *</label>
                        <input type="url" name="destination_url" id="destination_url" value="{{ old('destination_url') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="{{ __('campaigns.destination_url_placeholder') }}"
                               dir="ltr">
                    </div>
                    <div>
                        <label for="display_url" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.display_url_label') }}</label>
                        <input type="text" name="display_url" id="display_url" value="{{ old('display_url') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="{{ __('campaigns.display_url_placeholder') }}"
                               dir="ltr">
                        <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.display_url_hint') }}</p>
                    </div>

                    {{-- UTM Parameters --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.utm_parameters') }}</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label for="utm_source" class="sr-only">Source</label>
                                <input type="text" name="url_parameters[utm_source]" id="utm_source" value="{{ old('url_parameters.utm_source') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="{{ __('campaigns.utm_source_placeholder') }}"
                                       dir="ltr">
                            </div>
                            <div>
                                <label for="utm_medium" class="sr-only">Medium</label>
                                <input type="text" name="url_parameters[utm_medium]" id="utm_medium" value="{{ old('url_parameters.utm_medium') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="{{ __('campaigns.utm_medium_placeholder') }}"
                                       dir="ltr">
                            </div>
                            <div>
                                <label for="utm_campaign" class="sr-only">Campaign</label>
                                <input type="text" name="url_parameters[utm_campaign]" id="utm_campaign" value="{{ old('url_parameters.utm_campaign') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="{{ __('campaigns.utm_campaign_placeholder') }}"
                                       dir="ltr">
                            </div>
                        </div>
                    </div>

                    {{-- Tracking Pixel --}}
                    <div>
                        <label for="tracking_pixel_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.tracking_pixel_id_label') }}</label>
                        <input type="text" name="tracking_pixel_id" id="tracking_pixel_id" value="{{ old('tracking_pixel_id', $adSet->pixel_id) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="{{ __('campaigns.tracking_pixel_id_placeholder') }}"
                               dir="ltr">
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-end' }} {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('campaigns.cancel') }}
            </a>
            <button type="submit" name="action" value="save_draft"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('campaigns.save_as_draft') }}
            </button>
            <button type="submit"
                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                {{ __('campaigns.create_ad') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adForm() {
    return {
        adFormat: '{{ old('ad_format', 'image') }}',
        isDynamicCreative: {{ old('is_dynamic_creative') ? 'true' : 'false' }},
    }
}
</script>
@endpush
@endsection

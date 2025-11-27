@extends('layouts.admin')

@section('title', __('Create Ad') . ' - ' . $adSet->name)

@section('content')
<div class="space-y-6" x-data="adForm()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 20) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create Ad') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Ad</h1>
        <p class="mt-1 text-sm text-gray-500">
            Ad Set: <span class="font-medium">{{ $adSet->name }}</span>
            @if($campaign->platform)
                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ ucfirst($campaign->platform) }}
                </span>
            @endif
        </p>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc list-inside">
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ad Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                            </select>
                        </div>
                        <div>
                            <label for="ad_format" class="block text-sm font-medium text-gray-700">Ad Format *</label>
                            <select name="ad_format" id="ad_format" x-model="adFormat" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Creative Content</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="primary_text" class="block text-sm font-medium text-gray-700">Primary Text</label>
                        <textarea name="primary_text" id="primary_text" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="The main text that appears in your ad...">{{ old('primary_text') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Recommended: 125 characters max for best display</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="headline" class="block text-sm font-medium text-gray-700">Headline</label>
                            <input type="text" name="headline" id="headline" value="{{ old('headline') }}" maxlength="255"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Catchy headline for your ad">
                            <p class="mt-1 text-xs text-gray-500">Max 40 characters recommended</p>
                        </div>
                        <div>
                            <label for="call_to_action" class="block text-sm font-medium text-gray-700">Call to Action</label>
                            <select name="call_to_action" id="call_to_action"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Select CTA</option>
                                @foreach($callToActions as $value => $label)
                                    <option value="{{ $value }}" {{ old('call_to_action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="description_text" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description_text" id="description_text" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="Additional description text...">{{ old('description_text') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Media</h3>

                {{-- Single Image/Video --}}
                <div x-show="adFormat === 'image' || adFormat === 'video' || adFormat === 'stories' || adFormat === 'reels'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700">Image URL</label>
                            <input type="url" name="image_url" id="image_url" value="{{ old('image_url') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="https://example.com/image.jpg">
                            <p class="mt-1 text-xs text-gray-500">Recommended: 1080x1080px (1:1) or 1200x628px (1.91:1)</p>
                        </div>
                        <div>
                            <label for="video_url" class="block text-sm font-medium text-gray-700">Video URL</label>
                            <input type="url" name="video_url" id="video_url" value="{{ old('video_url') }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="https://example.com/video.mp4">
                            <p class="mt-1 text-xs text-gray-500">Max 240 minutes, recommended 15-60 seconds</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="thumbnail_url" class="block text-sm font-medium text-gray-700">Thumbnail URL (for video)</label>
                        <input type="url" name="thumbnail_url" id="thumbnail_url" value="{{ old('thumbnail_url') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="https://example.com/thumbnail.jpg">
                    </div>
                </div>

                {{-- Carousel Cards --}}
                <div x-show="adFormat === 'carousel'" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Carousel Cards (2-10 cards)</label>
                    <div class="space-y-4" x-data="{ cards: [{}] }">
                        <template x-for="(card, index) in cards" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <span class="text-sm font-medium text-gray-700">Card <span x-text="index + 1"></span></span>
                                    <button type="button" @click="cards.splice(index, 1)" x-show="cards.length > 1"
                                            class="text-red-500 hover:text-red-700 text-sm">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <input type="url" :name="'carousel_cards[' + index + '][image_url]'" placeholder="Image URL"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <input type="text" :name="'carousel_cards[' + index + '][headline]'" placeholder="Headline" maxlength="40"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <input type="text" :name="'carousel_cards[' + index + '][description]'" placeholder="Description" maxlength="125"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <input type="url" :name="'carousel_cards[' + index + '][link]'" placeholder="Destination URL"
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="if(cards.length < 10) cards.push({})"
                                class="text-blue-600 hover:text-blue-800 text-sm" x-show="cards.length < 10">
                            <i class="fas fa-plus mr-1"></i> Add Card
                        </button>
                    </div>
                </div>

                {{-- Dynamic Creative Toggle --}}
                <div class="mt-6 pt-4 border-t border-gray-200">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_dynamic_creative" value="1" x-model="isDynamicCreative"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">Enable Dynamic Creative</span>
                    </label>
                    <p class="mt-1 ml-6 text-xs text-gray-500">Automatically generate and test different combinations of your creative assets.</p>
                </div>
            </div>
        </div>

        {{-- Destination & Tracking --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Destination & Tracking</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="destination_url" class="block text-sm font-medium text-gray-700">Destination URL *</label>
                        <input type="url" name="destination_url" id="destination_url" value="{{ old('destination_url') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="https://yourwebsite.com/landing-page">
                    </div>
                    <div>
                        <label for="display_url" class="block text-sm font-medium text-gray-700">Display URL</label>
                        <input type="text" name="display_url" id="display_url" value="{{ old('display_url') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="yourwebsite.com">
                        <p class="mt-1 text-xs text-gray-500">The URL shown in your ad (may differ from destination)</p>
                    </div>

                    {{-- UTM Parameters --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">UTM Parameters</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label for="utm_source" class="sr-only">Source</label>
                                <input type="text" name="url_parameters[utm_source]" id="utm_source" value="{{ old('url_parameters.utm_source') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="utm_source (e.g., facebook)">
                            </div>
                            <div>
                                <label for="utm_medium" class="sr-only">Medium</label>
                                <input type="text" name="url_parameters[utm_medium]" id="utm_medium" value="{{ old('url_parameters.utm_medium') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="utm_medium (e.g., cpc)">
                            </div>
                            <div>
                                <label for="utm_campaign" class="sr-only">Campaign</label>
                                <input type="text" name="url_parameters[utm_campaign]" id="utm_campaign" value="{{ old('url_parameters.utm_campaign') }}"
                                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="utm_campaign">
                            </div>
                        </div>
                    </div>

                    {{-- Tracking Pixel --}}
                    <div>
                        <label for="tracking_pixel_id" class="block text-sm font-medium text-gray-700">Tracking Pixel ID</label>
                        <input type="text" name="tracking_pixel_id" id="tracking_pixel_id" value="{{ old('tracking_pixel_id', $adSet->pixel_id) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Enter your pixel ID">
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" name="action" value="save_draft"
                    class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Save as Draft
            </button>
            <button type="submit"
                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                Create Ad
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

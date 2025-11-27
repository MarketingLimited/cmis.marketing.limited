@extends('layouts.admin')

@section('title', __('Edit Ad') . ' - ' . $ad->name)

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
            <a href="{{ route('orgs.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 15) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Edit') }}: {{ $ad->name }}</span>
        </nav>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Ad</h1>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <ul class="text-sm text-red-700 list-disc list-inside">
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ad Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $ad->name) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @foreach(['draft', 'active', 'paused', 'completed', 'archived'] as $status)
                                    <option value="{{ $status }}" {{ old('status', $ad->status) === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="ad_format" class="block text-sm font-medium text-gray-700">Ad Format</label>
                            <select name="ad_format" id="ad_format" x-model="adFormat" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
                <h3 class="text-lg font-medium text-gray-900 mb-4">Creative Content</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="primary_text" class="block text-sm font-medium text-gray-700">Primary Text</label>
                        <textarea name="primary_text" id="primary_text" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('primary_text', $ad->primary_text) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="headline" class="block text-sm font-medium text-gray-700">Headline</label>
                            <input type="text" name="headline" id="headline" value="{{ old('headline', $ad->headline) }}" maxlength="255"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="call_to_action" class="block text-sm font-medium text-gray-700">Call to Action</label>
                            <select name="call_to_action" id="call_to_action" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Select CTA</option>
                                @foreach($callToActions as $value => $label)
                                    <option value="{{ $value }}" {{ old('call_to_action', $ad->call_to_action) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="description_text" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description_text" id="description_text" rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('description_text', $ad->description_text) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Media --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Media</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700">Image URL</label>
                        <input type="url" name="image_url" id="image_url" value="{{ old('image_url', $ad->image_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="video_url" class="block text-sm font-medium text-gray-700">Video URL</label>
                        <input type="url" name="video_url" id="video_url" value="{{ old('video_url', $ad->video_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-4">
                    <label for="thumbnail_url" class="block text-sm font-medium text-gray-700">Thumbnail URL</label>
                    <input type="url" name="thumbnail_url" id="thumbnail_url" value="{{ old('thumbnail_url', $ad->thumbnail_url) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>
        </div>

        {{-- Destination & Tracking --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Destination & Tracking</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="destination_url" class="block text-sm font-medium text-gray-700">Destination URL</label>
                        <input type="url" name="destination_url" id="destination_url" value="{{ old('destination_url', $ad->destination_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="display_url" class="block text-sm font-medium text-gray-700">Display URL</label>
                        <input type="text" name="display_url" id="display_url" value="{{ old('display_url', $ad->display_url) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @php $urlParams = $ad->url_parameters ?? []; @endphp
                        <input type="text" name="url_parameters[utm_source]" value="{{ old('url_parameters.utm_source', $urlParams['utm_source'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="utm_source">
                        <input type="text" name="url_parameters[utm_medium]" value="{{ old('url_parameters.utm_medium', $urlParams['utm_medium'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="utm_medium">
                        <input type="text" name="url_parameters[utm_campaign]" value="{{ old('url_parameters.utm_campaign', $urlParams['utm_campaign'] ?? '') }}"
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="utm_campaign">
                    </div>
                    <div>
                        <label for="tracking_pixel_id" class="block text-sm font-medium text-gray-700">Tracking Pixel ID</label>
                        <input type="text" name="tracking_pixel_id" id="tracking_pixel_id" value="{{ old('tracking_pixel_id', $ad->tracking_pixel_id) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-between">
            <form action="{{ route('org.campaigns.ad-sets.ads.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                  method="POST" onsubmit="return confirm('Delete this ad?');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm text-red-600 hover:text-red-700"><i class="fas fa-trash mr-1"></i> Delete</button>
            </form>
            <div class="flex space-x-3">
                <a href="{{ route('org.campaigns.ad-sets.ads.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">Save Changes</button>
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

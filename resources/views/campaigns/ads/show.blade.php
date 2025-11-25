@extends('layouts.app')

@section('title', $ad->name . ' - Ad')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
            <li><a href="{{ route('org.campaigns.index', $currentOrg) }}" class="text-gray-500 hover:text-gray-700">Campaigns</a></li>
            <li><i class="fas fa-chevron-right text-gray-400 mx-2"></i><a href="{{ route('org.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="text-gray-500 hover:text-gray-700">{{ Str::limit($adSet->name, 15) }}</a></li>
            <li><i class="fas fa-chevron-right text-gray-400 mx-2"></i><span class="text-gray-700 font-medium">{{ $ad->name }}</span></li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                {{ $ad->name }}
                @php
                    $statusColors = ['draft' => 'bg-gray-100 text-gray-800', 'active' => 'bg-green-100 text-green-800', 'paused' => 'bg-yellow-100 text-yellow-800'];
                @endphp
                <span class="ml-3 px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$ad->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($ad->status) }}
                </span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Format: {{ ucfirst($ad->ad_format ?? 'Unknown') }}
                @if($ad->external_ad_id)
                    <span class="ml-2 text-green-600"><i class="fas fa-check-circle"></i> Synced</span>
                @endif
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('org.campaigns.ad-sets.ads.preview', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-eye mr-2"></i> Preview
            </a>
            <a href="{{ route('org.campaigns.ad-sets.ads.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Creative Preview --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Creative Preview</h3>
                    <div class="bg-gray-100 rounded-lg p-4">
                        @if($ad->image_url || $ad->video_url)
                            <div class="max-w-md mx-auto">
                                @if($ad->video_url)
                                    <video controls class="w-full rounded-lg" poster="{{ $ad->thumbnail_url }}">
                                        <source src="{{ $ad->video_url }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ $ad->image_url }}" alt="{{ $ad->name }}" class="w-full rounded-lg">
                                @endif
                                <div class="mt-4 p-4 bg-white rounded-lg shadow-sm">
                                    @if($ad->headline)
                                        <h4 class="font-semibold text-gray-900">{{ $ad->headline }}</h4>
                                    @endif
                                    @if($ad->primary_text)
                                        <p class="mt-2 text-gray-600 text-sm">{{ $ad->primary_text }}</p>
                                    @endif
                                    @if($ad->call_to_action)
                                        <button class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded">
                                            {{ str_replace('_', ' ', $ad->call_to_action) }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <p>No media uploaded</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Ad Details --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ad Details</h3>
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Primary Text</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->primary_text ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Headline</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->headline ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->description_text ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Call to Action</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->call_to_action ? str_replace('_', ' ', $ad->call_to_action) : 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Destination URL</dt>
                            <dd class="mt-1 text-sm text-blue-600 break-all">
                                @if($ad->destination_url)
                                    <a href="{{ $ad->destination_url }}" target="_blank">{{ $ad->destination_url }}</a>
                                @else
                                    <span class="text-gray-900">Not set</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Review Status --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Review Status</h3>
                    @if($ad->review_status)
                        @php
                            $reviewColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'in_review' => 'bg-blue-100 text-blue-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $reviewColors[$ad->review_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $ad->review_status)) }}
                        </span>
                        @if($ad->review_feedback)
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-600">{{ $ad->review_feedback }}</p>
                            </div>
                        @endif
                    @else
                        <p class="text-gray-500 text-sm">Not submitted for review</p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <form action="{{ route('org.campaigns.ad-sets.ads.status', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}" method="POST">
                            @csrf @method('PATCH')
                            @if($ad->status === 'active')
                                <input type="hidden" name="status" value="paused">
                                <button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-pause mr-2"></i> Pause Ad
                                </button>
                            @else
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full px-4 py-2 border border-green-300 rounded-md text-sm text-green-700 bg-green-50 hover:bg-green-100">
                                    <i class="fas fa-play mr-2"></i> Activate Ad
                                </button>
                            @endif
                        </form>
                        <form action="{{ route('org.campaigns.ad-sets.ads.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-copy mr-2"></i> Duplicate
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span>{{ $ad->created_at->format('M d, Y') }}</span></div>
                    <div class="flex justify-between mt-2"><span class="text-gray-500">Updated</span><span>{{ $ad->updated_at->diffForHumans() }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

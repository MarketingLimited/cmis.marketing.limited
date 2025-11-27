@extends('layouts.admin')

@section('title', __('Ads') . ' - ' . $adSet->name)

@section('content')
<div class="space-y-6">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($campaign->name, 20) }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 20) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Ads') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ads</h1>
            <p class="mt-1 text-sm text-gray-500">
                Ad Set: <span class="font-medium">{{ $adSet->name }}</span>
            </p>
        </div>
        <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Create Ad
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Ads Grid --}}
    @if($ads->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($ads as $ad)
                <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Ad Preview Image --}}
                    <div class="h-48 bg-gray-100 relative">
                        @if($ad->thumbnail_url || $ad->image_url)
                            <img src="{{ $ad->thumbnail_url ?? $ad->image_url }}" alt="{{ $ad->name }}"
                                 class="w-full h-full object-cover">
                        @elseif($ad->video_url)
                            <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                <i class="fas fa-play-circle text-white text-4xl"></i>
                            </div>
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-image text-gray-300 text-4xl"></i>
                            </div>
                        @endif
                        {{-- Status Badge --}}
                        @php
                            $statusColors = [
                                'draft' => 'bg-gray-500',
                                'active' => 'bg-green-500',
                                'paused' => 'bg-yellow-500',
                                'completed' => 'bg-blue-500',
                                'archived' => 'bg-red-500',
                            ];
                        @endphp
                        <span class="absolute top-2 right-2 px-2 py-1 text-xs font-semibold text-white rounded {{ $statusColors[$ad->status] ?? 'bg-gray-500' }}">
                            {{ ucfirst($ad->status) }}
                        </span>
                        {{-- Format Badge --}}
                        @if($ad->ad_format)
                            <span class="absolute top-2 left-2 px-2 py-1 text-xs font-semibold text-gray-700 bg-white rounded shadow">
                                {{ ucfirst($ad->ad_format) }}
                            </span>
                        @endif
                    </div>

                    {{-- Ad Info --}}
                    <div class="p-4">
                        <a href="{{ route('org.campaigns.ad-sets.ads.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                           class="text-lg font-medium text-gray-900 hover:text-blue-600">
                            {{ $ad->name }}
                        </a>
                        @if($ad->headline)
                            <p class="mt-1 text-sm text-gray-600 truncate">{{ $ad->headline }}</p>
                        @endif
                        @if($ad->primary_text)
                            <p class="mt-1 text-sm text-gray-500 truncate">{{ Str::limit($ad->primary_text, 80) }}</p>
                        @endif

                        {{-- Review Status --}}
                        @if($ad->review_status)
                            <div class="mt-3">
                                @php
                                    $reviewColors = [
                                        'pending' => 'text-yellow-600 bg-yellow-50',
                                        'approved' => 'text-green-600 bg-green-50',
                                        'rejected' => 'text-red-600 bg-red-50',
                                        'in_review' => 'text-blue-600 bg-blue-50',
                                    ];
                                    $reviewIcons = [
                                        'pending' => 'clock',
                                        'approved' => 'check-circle',
                                        'rejected' => 'times-circle',
                                        'in_review' => 'sync',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 text-xs rounded {{ $reviewColors[$ad->review_status] ?? 'text-gray-600 bg-gray-50' }}">
                                    <i class="fas fa-{{ $reviewIcons[$ad->review_status] ?? 'question' }} mr-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $ad->review_status)) }}
                                </span>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="mt-4 flex items-center justify-between pt-3 border-t border-gray-100">
                            <div class="flex space-x-2">
                                <a href="{{ route('org.campaigns.ad-sets.ads.preview', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                   class="text-gray-400 hover:text-gray-600" title="Preview">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('org.campaigns.ad-sets.ads.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                   class="text-gray-400 hover:text-blue-600" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('org.campaigns.ad-sets.ads.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-gray-600" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                            </div>
                            <form action="{{ route('org.campaigns.ad-sets.ads.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('Delete this ad?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($ads->hasPages())
            <div class="mt-6">
                {{ $ads->links() }}
            </div>
        @endif
    @else
        <div class="bg-white shadow rounded-lg p-12 text-center">
            <i class="fas fa-ad text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No ads yet</h3>
            <p class="mt-1 text-sm text-gray-500">Create your first ad to start reaching your audience.</p>
            <div class="mt-6">
                <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Create Ad
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

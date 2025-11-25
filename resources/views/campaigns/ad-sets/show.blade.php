@extends('layouts.app')

@section('title', $adSet->name . ' - Ad Set')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li>
                <a href="{{ route('org.campaigns.index', $currentOrg) }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bullhorn mr-1"></i> Campaigns
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('org.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        {{ $campaign->name }}
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('org.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        Ad Sets
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700 font-medium">{{ $adSet->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                {{ $adSet->name }}
                @php
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'active' => 'bg-green-100 text-green-800',
                        'paused' => 'bg-yellow-100 text-yellow-800',
                        'completed' => 'bg-blue-100 text-blue-800',
                        'archived' => 'bg-red-100 text-red-800',
                    ];
                @endphp
                <span class="ml-3 px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$adSet->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($adSet->status) }}
                </span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Campaign: <span class="font-medium">{{ $campaign->name }}</span>
                @if($adSet->external_ad_set_id)
                    <span class="ml-2 text-green-600"><i class="fas fa-check-circle"></i> Synced to platform</span>
                @endif
            </p>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-plus mr-2"></i> Add Ad
            </a>
            <a href="{{ route('org.campaigns.ad-sets.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Overview Stats --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Budget</p>
                            <p class="text-lg font-semibold text-gray-900">
                                @if($adSet->budget_type === 'daily' && $adSet->daily_budget)
                                    ${{ number_format($adSet->daily_budget, 2) }}/day
                                @elseif($adSet->lifetime_budget)
                                    ${{ number_format($adSet->lifetime_budget, 2) }}
                                @else
                                    Not set
                                @endif
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Bid Strategy</p>
                            <p class="text-lg font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $adSet->bid_strategy ?? 'Auto')) }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Optimization</p>
                            <p class="text-lg font-semibold text-gray-900">{{ str_replace('_', ' ', $adSet->optimization_goal ?? 'Not set') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-500">Ads</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $adSet->ads->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Schedule</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Start Date</p>
                            <p class="text-gray-900">
                                {{ $adSet->start_time ? $adSet->start_time->format('M d, Y g:i A') : 'Not scheduled' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">End Date</p>
                            <p class="text-gray-900">
                                {{ $adSet->end_time ? $adSet->end_time->format('M d, Y g:i A') : 'No end date' }}
                            </p>
                        </div>
                    </div>
                    @if($adSet->isRunning())
                        <div class="mt-4 p-3 bg-green-50 rounded-lg">
                            <p class="text-sm text-green-800"><i class="fas fa-play-circle mr-1"></i> This ad set is currently running</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ads List --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Ads</h3>
                    <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                       class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Add Ad
                    </a>
                </div>
                @if($adSet->ads->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($adSet->ads as $ad)
                            <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        @if($ad->thumbnail_url || $ad->image_url)
                                            <img src="{{ $ad->thumbnail_url ?? $ad->image_url }}" alt="" class="w-16 h-16 object-cover rounded-lg mr-4">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 rounded-lg mr-4 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('org.campaigns.ad-sets.ads.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                               class="text-blue-600 hover:text-blue-900 font-medium">
                                                {{ $ad->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">{{ ucfirst($ad->ad_format ?? 'Unknown format') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$ad->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($ad->status) }}
                                        </span>
                                        @if($ad->review_status)
                                            @php
                                                $reviewColors = [
                                                    'pending' => 'text-yellow-600',
                                                    'approved' => 'text-green-600',
                                                    'rejected' => 'text-red-600',
                                                    'in_review' => 'text-blue-600',
                                                ];
                                            @endphp
                                            <span class="{{ $reviewColors[$ad->review_status] ?? 'text-gray-600' }} text-sm">
                                                <i class="fas fa-{{ $ad->review_status === 'approved' ? 'check' : ($ad->review_status === 'rejected' ? 'times' : 'clock') }}-circle"></i>
                                                {{ ucfirst(str_replace('_', ' ', $ad->review_status)) }}
                                            </span>
                                        @endif
                                        <a href="{{ route('org.campaigns.ad-sets.ads.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                           class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <a href="{{ route('org.campaigns.ad-sets.ads.index', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                           class="text-sm text-blue-600 hover:text-blue-800">
                            View all ads <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                @else
                    <div class="px-4 py-12 text-center">
                        <i class="fas fa-ad text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500">No ads yet</p>
                        <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                           class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            <i class="fas fa-plus mr-1"></i> Create your first ad
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Targeting Summary --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Targeting</h3>
                    <dl class="space-y-3">
                        @if($adSet->age_range)
                            <div>
                                <dt class="text-sm text-gray-500">Age Range</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $adSet->age_range['min'] ?? '18' }} - {{ $adSet->age_range['max'] ?? '65+' }}
                                </dd>
                            </div>
                        @endif
                        @if($adSet->genders)
                            <div>
                                <dt class="text-sm text-gray-500">Gender</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ is_array($adSet->genders) ? implode(', ', array_map('ucfirst', $adSet->genders)) : 'All' }}
                                </dd>
                            </div>
                        @endif
                        @if($adSet->locations)
                            <div>
                                <dt class="text-sm text-gray-500">Locations</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ is_array($adSet->locations) ? count($adSet->locations) . ' location(s)' : 'Not specified' }}
                                </dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm text-gray-500">Placements</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                {{ $adSet->automatic_placements ? 'Automatic (Advantage+)' : 'Manual' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Sync Info --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Platform Sync</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Sync Status</dt>
                            <dd class="text-sm font-medium">
                                @if($adSet->sync_status === 'synced')
                                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Synced</span>
                                @elseif($adSet->sync_status === 'error')
                                    <span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i> Error</span>
                                @else
                                    <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i> Pending</span>
                                @endif
                            </dd>
                        </div>
                        @if($adSet->external_ad_set_id)
                            <div>
                                <dt class="text-sm text-gray-500">External ID</dt>
                                <dd class="text-sm font-medium text-gray-900 font-mono">{{ $adSet->external_ad_set_id }}</dd>
                            </div>
                        @endif
                        @if($adSet->last_synced_at)
                            <div>
                                <dt class="text-sm text-gray-500">Last Synced</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $adSet->last_synced_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <form action="{{ route('org.campaigns.ad-sets.status', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            @if($adSet->status === 'active')
                                <input type="hidden" name="status" value="paused">
                                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-pause mr-2"></i> Pause Ad Set
                                </button>
                            @elseif($adSet->status === 'paused' || $adSet->status === 'draft')
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100">
                                    <i class="fas fa-play mr-2"></i> Activate Ad Set
                                </button>
                            @endif
                        </form>
                        <form action="{{ route('org.campaigns.ad-sets.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-copy mr-2"></i> Duplicate Ad Set
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Created</dt>
                            <dd class="text-gray-900">{{ $adSet->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Updated</dt>
                            <dd class="text-gray-900">{{ $adSet->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

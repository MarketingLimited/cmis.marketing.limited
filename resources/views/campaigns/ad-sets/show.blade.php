@extends('layouts.admin')

@section('title', $adSet->name . ' - ' . __('campaigns.ad_set'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('campaigns.campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ $campaign->name }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ __('campaigns.ad_sets') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $adSet->name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex justify-between items-start mb-6 {{ $isRtl ? 'flex-row-reverse' : '' }}">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
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
                <span class="{{ $isRtl ? 'mr-3' : 'ml-3' }} px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$adSet->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ __('campaigns.status.' . $adSet->status) }}
                </span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('campaigns.campaign_label') }}: <span class="font-medium">{{ $campaign->name }}</span>
                @if($adSet->external_ad_set_id)
                    <span class="{{ $isRtl ? 'mr-2 flex-row-reverse' : 'ml-2' }} text-green-600 inline-flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> {{ __('campaigns.synced_to_platform') }}
                    </span>
                @endif
            </p>
        </div>
        <div class="flex {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-plus {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.add_ad') }}
            </a>
            <a href="{{ route('org.campaigns.ad-sets.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-edit {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.edit') }}
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-3 text-right' : 'ml-3' }}">
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.overview') }}</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.budget') }}</p>
                            <p class="text-lg font-semibold text-gray-900">
                                @if($adSet->budget_type === 'daily' && $adSet->daily_budget)
                                    {{ $isRtl ? 'ر.س' : '$' }}{{ number_format($adSet->daily_budget, 2) }}{{ __('campaigns.per_day') }}
                                @elseif($adSet->lifetime_budget)
                                    {{ $isRtl ? 'ر.س' : '$' }}{{ number_format($adSet->lifetime_budget, 2) }}
                                @else
                                    {{ __('campaigns.not_set') }}
                                @endif
                            </p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.bid_strategy') }}</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $adSet->bid_strategy ? __('campaigns.bid_strategies.' . $adSet->bid_strategy) : __('campaigns.auto') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.optimization') }}</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $adSet->optimization_goal ?? __('campaigns.not_set') }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4 {{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.ads') }}</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $adSet->ads->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.schedule') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.start_date') }}</p>
                            <p class="text-gray-900">
                                {{ $adSet->start_time ? $adSet->start_time->format('M d, Y g:i A') : __('campaigns.not_scheduled') }}
                            </p>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-gray-500">{{ __('campaigns.end_date') }}</p>
                            <p class="text-gray-900">
                                {{ $adSet->end_time ? $adSet->end_time->format('M d, Y g:i A') : __('campaigns.no_end_date') }}
                            </p>
                        </div>
                    </div>
                    @if($adSet->isRunning())
                        <div class="mt-4 p-3 bg-green-50 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                            <p class="text-sm text-green-800 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                <i class="fas fa-play-circle"></i> {{ __('campaigns.ad_set_currently_running') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ads List --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('campaigns.ads') }}</h3>
                    <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                        <i class="fas fa-plus"></i> {{ __('campaigns.add_ad') }}
                    </a>
                </div>
                @if($adSet->ads->count() > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($adSet->ads as $ad)
                            <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        @if($ad->thumbnail_url || $ad->image_url)
                                            <img src="{{ $ad->thumbnail_url ?? $ad->image_url }}" alt="" class="w-16 h-16 object-cover rounded-lg {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 rounded-lg {{ $isRtl ? 'ml-4' : 'mr-4' }} flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            </div>
                                        @endif
                                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                                            <a href="{{ route('org.campaigns.ad-sets.ads.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
                                               class="text-blue-600 hover:text-blue-900 font-medium">
                                                {{ $ad->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">{{ $ad->ad_format ? __('campaigns.format_' . $ad->ad_format) : __('campaigns.unknown_format') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center {{ $isRtl ? 'space-x-reverse space-x-4' : 'space-x-4' }}">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$ad->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ __('campaigns.status.' . $ad->status) }}
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
                                            <span class="{{ $reviewColors[$ad->review_status] ?? 'text-gray-600' }} text-sm {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                                <i class="fas fa-{{ $ad->review_status === 'approved' ? 'check' : ($ad->review_status === 'rejected' ? 'times' : 'clock') }}-circle"></i>
                                                {{ __('campaigns.review_status.' . $ad->review_status) }}
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
                           class="text-sm text-blue-600 hover:text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                            {{ __('campaigns.view_all_ads') }} <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }}"></i>
                        </a>
                    </div>
                @else
                    <div class="px-4 py-12 text-center {{ $isRtl ? 'text-right' : '' }}">
                        <i class="fas fa-ad text-gray-400 text-3xl mb-3"></i>
                        <p class="text-gray-500">{{ __('campaigns.no_ads_yet') }}</p>
                        <a href="{{ route('org.campaigns.ad-sets.ads.create', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                           class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i class="fas fa-plus {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i> {{ __('campaigns.create_first_ad') }}
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.targeting_label') }}</h3>
                    <dl class="space-y-3">
                        @if($adSet->age_range)
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <dt class="text-sm text-gray-500">{{ __('campaigns.age_range') }}</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ $adSet->age_range['min'] ?? '18' }} - {{ $adSet->age_range['max'] ?? '65+' }}
                                </dd>
                            </div>
                        @endif
                        @if($adSet->genders)
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <dt class="text-sm text-gray-500">{{ __('campaigns.gender') }}</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ is_array($adSet->genders) ? implode(', ', array_map('ucfirst', $adSet->genders)) : __('campaigns.all') }}
                                </dd>
                            </div>
                        @endif
                        @if($adSet->locations)
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <dt class="text-sm text-gray-500">{{ __('campaigns.locations') }}</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    {{ is_array($adSet->locations) ? count($adSet->locations) . ' ' . __('campaigns.locations_count') : __('campaigns.not_specified') }}
                                </dd>
                            </div>
                        @endif
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm text-gray-500">{{ __('campaigns.placements') }}</dt>
                            <dd class="text-sm font-medium text-gray-900">
                                {{ $adSet->automatic_placements ? __('campaigns.automatic_advantage_plus') : __('campaigns.manual') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Sync Info --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platform_sync') }}</h3>
                    <dl class="space-y-3">
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm text-gray-500">{{ __('campaigns.sync_status') }}</dt>
                            <dd class="text-sm font-medium">
                                @if($adSet->sync_status === 'synced')
                                    <span class="text-green-600 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> {{ __('campaigns.synced') }}
                                    </span>
                                @elseif($adSet->sync_status === 'error')
                                    <span class="text-red-600 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ __('campaigns.error') }}
                                    </span>
                                @else
                                    <span class="text-yellow-600 {{ $isRtl ? 'flex-row-reverse' : '' }} inline-flex items-center gap-1">
                                        <i class="fas fa-clock"></i> {{ __('campaigns.pending') }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                        @if($adSet->external_ad_set_id)
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <dt class="text-sm text-gray-500">{{ __('campaigns.external_id') }}</dt>
                                <dd class="text-sm font-medium text-gray-900 font-mono">{{ $adSet->external_ad_set_id }}</dd>
                            </div>
                        @endif
                        @if($adSet->last_synced_at)
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <dt class="text-sm text-gray-500">{{ __('campaigns.last_synced') }}</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $adSet->last_synced_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.quick_actions') }}</h3>
                    <div class="space-y-3">
                        <form action="{{ route('org.campaigns.ad-sets.status', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            @if($adSet->status === 'active')
                                <input type="hidden" name="status" value="paused">
                                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <i class="fas fa-pause {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.pause_ad_set') }}
                                </button>
                            @elseif($adSet->status === 'paused' || $adSet->status === 'draft')
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-green-300 rounded-md text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <i class="fas fa-play {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.activate_ad_set') }}
                                </button>
                            @endif
                        </form>
                        <form action="{{ route('org.campaigns.ad-sets.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-copy {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.duplicate_ad_set') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <dt class="text-gray-500">{{ __('campaigns.created') }}</dt>
                            <dd class="text-gray-900">{{ $adSet->created_at->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <dt class="text-gray-500">{{ __('campaigns.updated') }}</dt>
                            <dd class="text-gray-900">{{ $adSet->updated_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
